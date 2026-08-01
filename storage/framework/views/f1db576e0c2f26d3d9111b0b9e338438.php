<?php
  $baseUnits = $institution->medicalUnits->take(5)->values();
  $subunitGroups = [
    'consulting' => ['title' => 'Catalogo de consultorios', 'location' => 'Consulta externa', 'prefix' => 'C'],
    'infusion' => ['title' => 'Catalogo de salas de infusion', 'location' => 'Oncologia / Infusion', 'prefix' => 'SI'],
    'operating' => ['title' => 'Catalogo de quirofanos', 'location' => 'Cirugia', 'prefix' => 'Q'],
    'recovery' => ['title' => 'Catalogo de salas de recuperacion', 'location' => 'Recuperacion', 'prefix' => 'SR'],
  ];
  $totalSubunits = $baseUnits->count() * count($subunitGroups);
?>

<section class="institution-subunit-summary">
  <div><h2>Catalogo de subunidades</h2><p><?php echo e($totalSubunits); ?> visibles - <?php echo e($totalSubunits); ?> subunidades institucionales</p></div>
  <button type="button" data-export-subunits="all">&darr; Descargar Excel</button>
</section>

<div class="institution-subunit-groups">
  <?php $__currentLoopData = $subunitGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <section class="institution-subunit-card" data-subunit-group="<?php echo e($groupKey); ?>">
      <header>
        <div><h2><?php echo e($group['title']); ?></h2><p><?php echo e($baseUnits->count()); ?> subunidades</p></div>
        <div><button type="button" data-toggle-subunits><?php echo e($loop->first ? 'Ocultar' : 'Mostrar'); ?></button><button type="button" data-export-subunits="<?php echo e($groupKey); ?>">Excel</button></div>
      </header>
      <div class="institution-subunit-table-wrap" <?php if(! $loop->first): ?> hidden <?php endif; ?>>
        <table class="institution-subunit-table">
          <thead><tr><th>Unidad</th><th>Ubicacion</th><th>Piso</th><th>Numero de unidad</th><th>Capacidad simultanea</th><th>Horario de atencion</th><th>Estatus</th></tr></thead>
          <tbody>
            <?php $__currentLoopData = $baseUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $capacity = (($loop->index + strlen($groupKey)) % 2) + 1;
                $subunitNumber = $group['prefix'].'-'.str_pad((string) (16 + $loop->index), 2, '0', STR_PAD_LEFT);
              ?>
              <tr data-subunit-row>
                <td><strong><?php echo e($unit->name); ?></strong><small><?php echo e($unit->clues ?? $unit->external_id ?? 'Sin CLUES'); ?></small></td>
                <td><?php echo e($group['location']); ?></td><td><?php echo e(data_get($unit->metadata, 'floor', 'PB')); ?></td>
                <td><strong><?php echo e($subunitNumber); ?></strong><small><?php echo e(match ($groupKey) { 'consulting' => 'consultorio', 'infusion' => 'sala de infusion', 'operating' => 'quirofano', default => 'sala de recuperacion' }); ?></small></td>
                <td><?php echo e($capacity); ?> <?php echo e($capacity === 1 ? 'paciente simultaneo' : 'pacientes simultaneos'); ?></td>
                <td>Lunes, Martes, Miercoles, Jueves, Viernes | 08:00 - 16:00</td>
                <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['institution-subunit-status', 'is-inactive' => $unit->status !== 'active']); ?>"><?php echo e($unit->status === 'active' ? 'Activo' : 'Inactivo'); ?></span></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<script>
  (() => {
    const groups = [...document.querySelectorAll('[data-subunit-group]')];
    groups.forEach((group) => {
      const button = group.querySelector('[data-toggle-subunits]');
      const table = group.querySelector('.institution-subunit-table-wrap');
      button.addEventListener('click', () => { table.hidden = !table.hidden; button.textContent = table.hidden ? 'Mostrar' : 'Ocultar'; });
    });
    const download = (scope) => {
      const selected = scope === 'all' ? groups : groups.filter((group) => group.dataset.subunitGroup === scope);
      const rows = [['Unidad', 'Ubicacion', 'Piso', 'Numero de unidad', 'Capacidad simultanea', 'Horario de atencion', 'Estatus']];
      selected.forEach((group) => group.querySelectorAll('[data-subunit-row]').forEach((row) => rows.push([...row.cells].map((cell) => cell.innerText.replace(/\s+/g, ' ').trim()))));
      const csv = rows.map((row) => row.map((cell) => `"${cell.replaceAll('"', '""')}"`).join(',')).join('\r\n');
      const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' })); link.download = `subunidades-${scope}.csv`; link.click(); URL.revokeObjectURL(link.href);
    };
    document.querySelectorAll('[data-export-subunits]').forEach((button) => button.addEventListener('click', () => download(button.dataset.exportSubunits)));
  })();
</script>
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/institution/_subunits.blade.php ENDPATH**/ ?>