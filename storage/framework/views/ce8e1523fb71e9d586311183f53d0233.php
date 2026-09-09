

<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Facturacion</p>
          <h1>Facturacion hospitalaria</h1>
          <p>Control de CFDI, conceptos, pagos, rechazos y comparativo contra monto autorizado.</p>
        </div>
      </section>

  <?php if($abilities['manage_billing']): ?>
    <form class="insurance-form insurance-entry-form compact" method="post" action="<?php echo e(route('insurance.invoices.store')); ?>">
      <?php echo csrf_field(); ?>
      <section class="form-section insurance-form-panel">
        <h2>Registrar factura</h2>
        <div class="form-grid">
          <label>Hospitalizacion
            <select name="hospitalization_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $hospitalizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospitalization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($hospitalization->id); ?>"><?php echo e($hospitalization->patient?->full_name); ?> / <?php echo e($hospitalization->hospital_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Hospital
            <select name="hospital_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $hospitals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hospital): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($hospital->id); ?>"><?php echo e($hospital->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Proveedor
            <select name="provider_id">
              <option value="">Sin ligar</option>
              <?php $__currentLoopData = $providers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($provider->id); ?>"><?php echo e($provider->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </label>
          <label>Proveedor manual<input name="provider_name"></label>
          <label>RFC proveedor<input name="provider_rfc" maxlength="13"></label>
          <label>Factura<input name="invoice_number" required></label>
          <label>UUID fiscal<input name="fiscal_uuid"></label>
          <label>Fecha factura<input type="date" name="invoice_date"></label>
          <label>Concepto
            <select name="concept_type">
              <option value="room">Habitacion</option>
              <option value="medical_fees">Honorarios</option>
              <option value="medications">Medicamentos</option>
              <option value="supplies">Material curacion</option>
              <option value="laboratory">Laboratorio</option>
              <option value="imaging">Imagenologia</option>
              <option value="operating_room">Quirofano</option>
              <option value="intensive_care">Terapia intensiva</option>
              <option value="other">Otros</option>
            </select>
          </label>
          <label>Subtotal<input type="number" step="0.01" min="0" name="subtotal" required></label>
          <label>IVA<input type="number" step="0.01" min="0" name="vat" value="0"></label>
          <label>Retenciones<input type="number" step="0.01" min="0" name="withholdings" value="0"></label>
          <label>Total<input type="number" step="0.01" min="0" name="total" required></label>
          <label>Moneda<input name="currency" value="MXN" maxlength="3" required></label>
          <label>Estatus
            <select name="status">
              <option value="received">Recibida</option>
              <option value="in_review">En revision</option>
              <option value="approved">Aprobada</option>
              <option value="rejected">Rechazada</option>
              <option value="paid">Pagada</option>
              <option value="partially_paid">Parcialmente pagada</option>
            </select>
          </label>
          <label class="span-2">Descripcion concepto<textarea name="concept"></textarea></label>
        </div>
        <button type="submit">Registrar factura</button>
      </section>
    </form>
  <?php endif; ?>

  <form class="filter-bar insurance-filter-bar" method="get">
    <label>Estatus
      <select name="status">
        <option value="">Todos</option>
        <?php $__currentLoopData = ['received', 'in_review', 'approved', 'rejected', 'paid', 'partially_paid']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($status); ?>" <?php if(request('status') === $status): echo 'selected'; endif; ?>><?php echo e($status); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </label>
    <label>Proveedor<input name="provider" value="<?php echo e(request('provider')); ?>"></label>
    <button type="submit">Filtrar</button>
  </form>

  <section class="table-card insurance-table-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Factura</th>
          <th>Hospitalizacion</th>
          <th>Proveedor</th>
          <th>Total</th>
          <th>Estatus</th>
          <th>Revision</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php ($authorized = (float) ($invoice->hospitalization?->authorized_amount ?? 0)); ?>
          <tr>
            <td>
              <strong><?php echo e($invoice->invoice_number); ?></strong>
              <span><?php echo e($invoice->fiscal_uuid ?? 'Sin UUID'); ?></span>
            </td>
            <td><?php echo e($invoice->hospitalization?->patient?->full_name ?? 'Sin ligar'); ?></td>
            <td><?php echo e($invoice->provider_name ?? $invoice->provider?->name ?? 'Sin proveedor'); ?></td>
            <td>$<?php echo e(number_format((float) $invoice->total, 2)); ?></td>
            <td><span class="badge"><?php echo e($invoice->status); ?></span></td>
            <td>
              <?php if(blank($invoice->xml_path)): ?>
                <span class="badge risk-high">Sin XML</span>
              <?php endif; ?>
              <?php if($authorized > 0 && (float) $invoice->total > $authorized): ?>
                <span class="badge risk-critical">Excede autorizado</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="6">Sin facturas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

      <div class="pagination-wrap"><?php echo e($invoices->links()); ?></div>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Facturacion hospitalaria'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\insurance\invoices\index.blade.php ENDPATH**/ ?>