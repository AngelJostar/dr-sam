<?php

namespace App\Support;

use App\Models\MedicalUnit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UnitAdministrationDemo
{
    public static function state(MedicalUnit $unit): array
    {
        return data_get($unit->metadata, 'administration_demo') ?? self::initial();
    }

    public static function initial(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $state = ['version' => 1, 'operations' => [], 'accounts' => [], 'charges' => [], 'payments' => [], 'invoices' => [], 'insurers' => [], 'authorizations' => [], 'documents' => []];
        foreach ([['INS-001', 'Aseguradora A', 'CONV-001', 'Hospitalización y consulta'], ['INS-002', 'Aseguradora B', 'CONV-002', 'Hospitalización y cirugía'], ['INS-003', 'Aseguradora C', '', '']] as [$id, $name, $agreement, $scope]) {
            $state['insurers'][] = compact('id', 'name', 'agreement', 'scope') + ['contact' => 'gestion@'.str($name)->slug().'.example', 'valid_until' => now()->endOfYear()->toDateString(), 'status' => $agreement ? 'active' : 'draft', 'notes' => 'Convenio de demostración.'];
        }
        foreach ([['081', 'Ana López', 'particular', null, 480000, 300000], ['082', 'Luis Pérez', 'insurer', 'INS-001', 1850000, 1000000], ['083', 'María Torres', 'agreement', null, 720000, 720000], ['084', 'José Ramírez', 'particular', null, 260000, 0], ['085', 'Elena Cruz', 'insurer', 'INS-002', 1200000, 800000], ['086', 'Paciente de ejemplo', 'insurer', 'INS-001', 600000, 0]] as [$number, $patient, $coverage, $insurer_id, $amount, $paid]) {
            $id = 'CTA-'.$number;
            $state['accounts'][] = compact('id', 'patient', 'coverage', 'insurer_id') + ['episode' => 'EPI-'.$number, 'opened_on' => $yesterday];
            $state['charges'][] = ['id' => 'CAR-'.$number, 'account_id' => $id, 'description' => 'Atención hospitalaria · Demo', 'amount' => $amount, 'date' => $yesterday];
            if ($paid) $state['payments'][] = ['id' => 'REC-'.str_pad((string) (91 + count($state['payments'])), 3, '0', STR_PAD_LEFT), 'account_id' => $id, 'amount' => $paid, 'date' => $today, 'method' => $number === '083' ? 'Tarjeta' : 'Transferencia', 'reference' => 'DEMO-'.$number];
        }
        $state['invoices'] = [
            ['id' => 'FAC-028', 'account_id' => 'CTA-083', 'receiver' => 'María Torres', 'tax_id' => 'XAXX010101000', 'postal_code' => '01000', 'use' => 'G03', 'status' => 'issued', 'amount' => 720000, 'date' => $today],
            ['id' => 'BOR-029', 'account_id' => 'CTA-081', 'receiver' => 'Ana López', 'tax_id' => '', 'postal_code' => '', 'use' => '', 'status' => 'draft', 'amount' => 480000, 'date' => $today],
        ];
        foreach ([['AUT-011', 'CTA-082', 'INS-001', 'Hospitalización', 1850000, null, 'review'], ['AUT-012', 'CTA-085', 'INS-002', 'Atención hospitalaria', 1200000, null, 'documents'], ['AUT-013', 'CTA-086', 'INS-001', 'Consulta y estudios', 600000, 600000, 'authorized']] as [$id, $account_id, $insurer_id, $service, $requested, $authorized, $status]) {
            $state['authorizations'][] = compact('id', 'account_id', 'insurer_id', 'service', 'requested', 'authorized', 'status') + ['date' => $today, 'responsible' => $status === 'documents' ? 'Admisión' : 'Gestión de aseguradoras', 'notes' => $status === 'documents' ? 'Adjuntar documentación solicitada.' : 'Seguimiento de demostración.', 'history' => [['date' => $today, 'text' => 'Solicitud de demostración registrada.']]];
        }
        return $state;
    }

    public static function balances(array $state, string $id): array
    {
        $charges = collect($state['charges'])->where('account_id', $id)->sum('amount');
        $payments = collect($state['payments'])->where('account_id', $id)->sum('amount');
        return ['charges' => $charges, 'payments' => $payments, 'balance' => $charges - $payments, 'status' => $charges > 0 && $charges === $payments ? 'paid' : ($payments > 0 ? 'open' : 'pending')];
    }

    public static function record(array $state, string $collection, string $id): array
    {
        return collect($state[$collection])->firstWhere('id', $id) ?? throw ValidationException::withMessages(['record' => 'El registro no pertenece a esta unidad o ya no está disponible.']);
    }

    private static function id(array $state, string $collection, string $prefix): string
    {
        $number = collect($state[$collection])->map(fn ($row) => (int) substr($row['id'], strrpos($row['id'], '-') + 1))->max() ?? 0;
        return $prefix.'-'.str_pad((string) ($number + 1), 3, '0', STR_PAD_LEFT);
    }

    private static function replace(array &$state, string $collection, array $record): void
    {
        $state[$collection] = collect($state[$collection])->reject(fn ($row) => $row['id'] === $record['id'])->push($record)->values()->all();
    }

    public static function apply(array $state, string $action, array $input): array
    {
        $money = ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'];
        $account = ['required', Rule::in(array_column($state['accounts'], 'id'))];
        $insurer = ['required', Rule::in(array_column($state['insurers'], 'id'))];
        $date = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
        $rules = match ($action) {
            'account' => ['patient' => 'required|string|max:180', 'episode' => 'required|string|max:80', 'coverage' => ['required', Rule::in(['particular', 'agreement', 'insurer'])], 'insurer_id' => ['nullable', 'required_if:coverage,insurer', Rule::in(array_column($state['insurers'], 'id'))], 'amount' => $money, 'description' => 'required|string|max:250'],
            'charge' => ['account_id' => $account, 'amount' => $money, 'description' => 'required|string|max:250', 'date' => $date],
            'payment' => ['account_id' => $account, 'amount' => $money, 'date' => $date, 'method' => ['required', Rule::in(['Efectivo', 'Tarjeta', 'Transferencia'])], 'reference' => 'nullable|string|max:150'],
            'insurer' => ['id' => ['nullable', Rule::in(array_column($state['insurers'], 'id'))], 'name' => 'required|string|max:180', 'agreement' => 'nullable|required_if:status,active|string|max:100', 'contact' => 'nullable|email|max:180', 'scope' => 'nullable|required_if:status,active|string|max:500', 'valid_until' => 'nullable|required_if:status,active|date_format:Y-m-d', 'status' => ['required', Rule::in(['active', 'draft', 'inactive'])], 'notes' => 'nullable|string|max:2000'],
            'invoice' => ['id' => ['nullable', Rule::in(array_column($state['invoices'], 'id'))], 'account_id' => $account, 'receiver' => 'required|string|max:180', 'tax_id' => 'nullable|required_if:status,issued|string|max:13', 'postal_code' => 'nullable|required_if:status,issued|digits:5', 'use' => 'nullable|required_if:status,issued|string|max:80', 'status' => ['required', Rule::in(['draft', 'issued'])]],
            'authorization' => ['account_id' => $account, 'insurer_id' => $insurer, 'service' => 'required|string|max:250', 'amount' => $money, 'responsible' => 'required|string|max:180', 'notes' => 'nullable|string|max:2000'],
            'response' => ['id' => ['required', Rule::in(array_column($state['authorizations'], 'id'))], 'status' => ['required', Rule::in(['review', 'documents', 'authorized', 'rejected'])], 'amount' => ['nullable', 'required_if:status,authorized', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'], 'notes' => 'required|string|max:2000'],
            'followup' => ['id' => ['required', Rule::in(array_column($state['authorizations'], 'id'))], 'notes' => 'required|string|max:2000'],
            'document' => ['kind' => ['required', Rule::in(['insurers', 'authorizations'])], 'id' => 'required|string|max:80', 'name' => 'required|string|max:180', 'path' => 'required|string|max:255', 'mime' => 'required|string|max:100'],
            default => throw ValidationException::withMessages(['action' => 'Acción no válida.']),
        };
        $data = Validator::make($input, $rules)->validate();
        $today = now()->toDateString();
        if ($action === 'account') {
            $id = self::id($state, 'accounts', 'CTA');
            $state['accounts'][] = ['id' => $id, 'patient' => $data['patient'], 'episode' => $data['episode'], 'coverage' => $data['coverage'], 'insurer_id' => $data['coverage'] === 'insurer' ? $data['insurer_id'] : null, 'opened_on' => $today];
            $state['charges'][] = ['id' => self::id($state, 'charges', 'CAR'), 'account_id' => $id, 'amount' => (int) round($data['amount'] * 100), 'description' => $data['description'], 'date' => $today];
        } elseif (in_array($action, ['charge', 'payment'], true)) {
            $collection = $action === 'payment' ? 'payments' : 'charges';
            $data['amount'] = (int) round($data['amount'] * 100);
            if ($action === 'payment' && $data['amount'] > self::balances($state, $data['account_id'])['balance']) throw ValidationException::withMessages(['amount' => 'El pago supera el saldo pendiente de la cuenta.']);
            $state[$collection][] = $data + ['id' => self::id($state, $collection, $action === 'payment' ? 'REC' : 'CAR')];
        } elseif ($action === 'insurer') {
            $data['id'] = ($data['id'] ?? null) ?: self::id($state, 'insurers', 'INS');
            self::replace($state, 'insurers', $data);
        } elseif ($action === 'invoice') {
            $previous = empty($data['id']) ? null : self::record($state, 'invoices', $data['id']);
            if ($previous && ($previous['status'] === 'issued' || $previous['account_id'] !== $data['account_id'])) throw ValidationException::withMessages(['id' => 'La factura emitida no se modifica ni cambia de cuenta.']);
            if (collect($state['invoices'])->contains(fn ($row) => $row['account_id'] === $data['account_id'] && $row['id'] !== ($data['id'] ?? null))) throw ValidationException::withMessages(['account_id' => 'Esta cuenta ya tiene un documento de facturación. Abre Completar o Ver detalle.']);
            $data['amount'] = self::balances($state, $data['account_id'])['charges'];
            $data['date'] = $today;
            $data['id'] = $previous['id'] ?? self::id($state, 'invoices', $data['status'] === 'issued' ? 'FAC' : 'BOR');
            self::replace($state, 'invoices', $data);
        } elseif ($action === 'authorization') {
            $related = self::record($state, 'accounts', $data['account_id']);
            if ($related['coverage'] !== 'insurer' || $related['insurer_id'] !== $data['insurer_id']) throw ValidationException::withMessages(['account_id' => 'La cuenta debe tener la cobertura de la aseguradora seleccionada.']);
            $data['requested'] = (int) round($data['amount'] * 100);
            unset($data['amount']);
            $state['authorizations'][] = $data + ['id' => self::id($state, 'authorizations', 'AUT'), 'date' => $today, 'status' => 'review', 'authorized' => null, 'history' => [['date' => $today, 'text' => 'Solicitud demo registrada.']]];
        } elseif (in_array($action, ['response', 'followup'], true)) {
            $record = self::record($state, 'authorizations', $data['id']);
            if ($action === 'response') {
                $authorized = $data['status'] === 'authorized' ? (int) round($data['amount'] * 100) : null;
                if ($authorized !== null && $authorized > $record['requested']) throw ValidationException::withMessages(['amount' => 'El importe autorizado supera el solicitado.']);
                $record['status'] = $data['status'];
                $record['authorized'] = $authorized;
            }
            $record['notes'] = $data['notes'];
            $record['history'][] = ['date' => now()->format('Y-m-d H:i'), 'text' => $data['notes']];
            self::replace($state, 'authorizations', $record);
        } elseif ($action === 'document') {
            self::record($state, $data['kind'], $data['id']);
            $data['record_id'] = $data['id'];
            $data['id'] = self::id($state, 'documents', 'DOC');
            $data['date'] = $today;
            $state['documents'][] = $data;
        }
        $state['version']++;
        return $state;
    }
}
