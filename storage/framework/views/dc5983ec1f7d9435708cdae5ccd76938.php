

<?php $__env->startSection('body_class', 'insurance-health-native-body'); ?>

<?php $__env->startSection('content'); ?>
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    <?php echo $__env->make('insurance._topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('insurance._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="insurance-health-native-workspace">
      <?php echo $__env->make('insurance._flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Entregas</p>
          <h1>Seguimiento de medicamentos</h1>
          <p>Entregas programadas, realizadas, atrasadas y evidencias operativas.</p>
        </div>
      </section>

      <form class="filter-bar insurance-filter-bar" method="get">
        <label>Paciente<input name="patient" value="<?php echo e(request('patient')); ?>" placeholder="Nombre"></label>
        <label>Estatus
          <select name="status">
            <option value="">Todos</option>
            <?php $__currentLoopData = ['pending' => 'Pendiente', 'in_route' => 'En ruta', 'delivered' => 'Entregado', 'not_delivered' => 'No entregado', 'rescheduled' => 'Reprogramado', 'cancelled' => 'Cancelado']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($key); ?>" <?php if(request('status') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <button type="submit">Filtrar</button>
      </form>

      <section class="table-card insurance-table-panel">
        <div class="insurance-health-native-table-title">
          <h2>Seguimiento de medicamentos</h2>
          <span><?php echo e($deliveries->total()); ?> registros</span>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Medicamento</th>
              <th>Programada</th>
              <th>Real</th>
              <th>Estatus</th>
              <th>Accion</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $deliveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><a class="text-link" href="<?php echo e(route('insurance.patients.show', $delivery->patient)); ?>"><?php echo e($delivery->patient?->full_name); ?></a></td>
                <td>
                  <strong><?php echo e($delivery->treatment?->medication_name ?? $delivery->medication?->name ?? 'Medicamento'); ?></strong>
                  <span><?php echo e($delivery->quantity_delivered); ?> piezas / <?php echo e($delivery->covered_period); ?></span>
                </td>
                <td><?php echo e($delivery->scheduled_delivery_date?->format('d/m/Y')); ?></td>
                <td><?php echo e($delivery->actual_delivery_date?->format('d/m/Y') ?? 'Pendiente'); ?></td>
                <td><span class="badge"><?php echo e($delivery->status); ?></span></td>
                <td>
                  <?php if($abilities['manage_deliveries']): ?>
                    <form class="table-action" method="post" action="<?php echo e(route('insurance.deliveries.status', $delivery)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <select name="status">
                        <?php $__currentLoopData = ['pending', 'in_route', 'delivered', 'not_delivered', 'rescheduled', 'cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <option value="<?php echo e($status); ?>" <?php if($delivery->status === $status): echo 'selected'; endif; ?>><?php echo e($status); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </select>
                      <input type="date" name="actual_delivery_date" value="<?php echo e($delivery->actual_delivery_date?->format('Y-m-d')); ?>">
                      <button type="submit">Actualizar</button>
                    </form>
                  <?php else: ?>
                    <span>Solo lectura</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="6">Sin entregas registradas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <div class="pagination-wrap"><?php echo e($deliveries->links()); ?></div>
    </main>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Entregas medicamentos'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/insurance/deliveries/index.blade.php ENDPATH**/ ?>