<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacyPrescriptionUpload;
use App\Services\Platform\PlatformAuditService;
use App\Support\MobileApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PharmacyPrescriptionUploadController extends Controller
{
    public function store(Request $request, PlatformAuditService $audit): JsonResponse
    {
        $patient = $request->user()->patient()->firstOrFail();
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png', 'max:8192'],
        ]);
        // A bounded private inbox. Uploading is not medical approval or dispensing.
        abort_if(PharmacyPrescriptionUpload::query()
            ->where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subDay())->count() >= 30, 429);
        $file = $request->file('file');
        $mime = $file->getMimeType();
        $extension = match ($mime) {
            'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png',
        };
        $path = $file->storeAs(
            'pharmacy-prescriptions/'.$request->user()->id,
            (string) str()->uuid().'.'.$extension, 'local'
        );
        if (! is_string($path) || $path === '') {
            return MobileApiResponse::error('storage_unavailable', 'No pudimos guardar la receta. Inténtalo nuevamente.', 503);
        }
        try {
            $upload = DB::transaction(function () use ($request, $patient, $file, $path, $mime, $extension, $audit): PharmacyPrescriptionUpload {
                // Never trust client ownership, status or storage paths.
                $original = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $stem = pathinfo($original, PATHINFO_FILENAME);
                $stem = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '', $stem));
                $name = mb_substr($stem ?: 'receta', 0, 160).'.'.$extension;
                $upload = PharmacyPrescriptionUpload::query()->create([
                    'user_id' => $request->user()->id, 'patient_id' => $patient->id,
                    'path' => $path, 'file_name' => $name, 'mime_type' => $mime,
                    'size_bytes' => $file->getSize(), 'status' => 'pending_review',
                ]);
                $audit->record($request, 'pharmacy.prescription.uploaded', $upload, 'digital_pharmacy');
                return $upload;
            });
        } catch (Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return MobileApiResponse::success([
            'id' => $upload->id, 'file_name' => $upload->file_name,
            'mime_type' => $upload->mime_type, 'size_bytes' => $upload->size_bytes,
            'status' => $upload->status, 'created_at' => $upload->created_at->toIso8601String(),
        ], 201);
    }

    public function download(Request $request, int $upload, PlatformAuditService $audit): StreamedResponse
    {
        $patient = $request->user()->patient()->firstOrFail();
        $record = PharmacyPrescriptionUpload::query()
            ->where('user_id', $request->user()->id)
            ->where('patient_id', $patient->id)->findOrFail($upload);
        abort_unless(Storage::disk('local')->exists($record->path), 404);
        $audit->record($request, 'pharmacy.prescription.downloaded', $record, 'digital_pharmacy');

        return Storage::disk('local')->download($record->path, $record->file_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
