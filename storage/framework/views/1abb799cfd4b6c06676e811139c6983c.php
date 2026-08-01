<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Documental</p>
          <h1>Control documental</h1>
          <p>Clasificacion de identificaciones, polizas, recetas, autorizaciones, estudios, facturas y evidencias.</p>
        </div>
      </section>

  <?php if($abilities['manage_documents']): ?>
    <form class="insurance-form insurance-entry-form compact" method="post" action="<?php echo e(route('insurance.documents.store')); ?>" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <section class="form-section insurance-form-panel">
        <h2>Subir documento</h2>
        <div class="form-grid">
          <label>Paciente
            <select name="patient_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Nombre<input name="name" required></label>
          <label>Tipo
            <select name="document_type">
              <option value="official_id">Identificacion oficial</option>
              <option value="policy">Poliza</option>
              <option value="prescription">Receta medica</option>
              <option value="treatment_authorization">Autorizacion tratamiento</option>
              <option value="clinical_summary">Resumen clinico</option>
              <option value="laboratory">Laboratorio</option>
              <option value="imaging">Imagen</option>
              <option value="invoice_pdf">Factura PDF</option>
              <option value="fiscal_xml">XML fiscal</option>
              <option value="medical_letter">Carta medica</option>
              <option value="informed_consent">Consentimiento informado</option>
              <option value="delivery_evidence">Evidencia entrega</option>
              <option value="other">Otros</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="current">Vigente</option>
              <option value="expired">Vencido</option>
              <option value="replaced">Sustituido</option>
              <option value="rejected">Rechazado</option>
            </select>
          </label>
          <label>Vence<input type="date" name="expires_at"></label>
          <label>Tratamiento
            <select name="treatment_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $treatments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $treatment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($treatment->id); ?>"><?php echo e($treatment->patient?->full_name); ?> / <?php echo e($treatment->medication_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Hospitalizacion
            <select name="hospitalization_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $hospitalizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospitalization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($hospitalization->id); ?>"><?php echo e($hospitalization->patient?->full_name); ?> / <?php echo e($hospitalization->hospital_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Factura
            <select name="invoice_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($invoice->id); ?>"><?php echo e($invoice->invoice_number); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label class="span-2">Archivo<input type="file" name="document_file"></label>
        </div>
        <button type="submit">Registrar documento</button>
      </section>
    </form>
  <?php endif; ?>

  <form class="filter-bar insurance-filter-bar" method="get">
    <label>Tipo<input name="type" value="<?php echo e(request('type')); ?>" placeholder="policy, fiscal_xml"></label>
    <label>Estatus
      <select name="status">
        <option value="">Todos</option>
        <?php $__currentLoopData = ['current', 'expired', 'replaced', 'rejected']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($status); ?>" <?php if(request('status') === $status): echo 'selected'; endif; ?>><?php echo e($status); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </label>
    <button type="submit">Filtrar</button>
  </form>

  <section class="table-card insurance-table-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Documento</th>
          <th>Paciente</th>
          <th>Tipo</th>
          <th>Carga</th>
          <th>Vence</th>
          <th>Estatus</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td>
              <strong><?php echo e($document->name); ?></strong>
              <span><?php echo e($document->file_path ?? 'Sin archivo fisico'); ?></span>
            </td>
            <td><?php echo e($document->patient?->full_name ?? 'Sin paciente'); ?></td>
            <td><?php echo e($document->document_type); ?></td>
            <td><?php echo e($document->loaded_at?->format('d/m/Y H:i')); ?></td>
            <td><?php echo e($document->expires_at?->format('d/m/Y') ?? 'Sin vencimiento'); ?></td>
            <td><span class="badge"><?php echo e($document->status); ?></span></td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="6">Sin documentos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

      <div class="pagination-wrap"><?php echo e($documents->links()); ?></div>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Documentos'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views\insurance\documents\index.blade.php ENDPATH**/ ?>