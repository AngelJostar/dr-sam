<header class="insurance-health-native-topbar" aria-label="Barra superior aseguradora salud">
  <strong>Aseguradora salud</strong>
  <span><?php echo e(strtoupper(auth()->user()?->name ?? 'Aseguradora Salud')); ?></span>
  <div class="insurance-health-native-session">
    <button type="button" aria-label="Notificaciones">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
    </button>
    <div>
      <strong>Usuario activo</strong>
      <small>Control clinico</small>
    </div>
    <form method="post" action="<?php echo e(route('logout')); ?>" class="insurance-health-native-logout">
      <?php echo csrf_field(); ?>
      <button type="submit">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
        Cerrar sesion
      </button>
    </form>
  </div>
</header>
<?php /**PATH C:\laragon\www\dr-sam\resources\views\insurance\_topbar.blade.php ENDPATH**/ ?>