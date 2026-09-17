<?php
  $carouselItems = $institutionCatalogUi['carousel'] ?? [];
  $filterItems = $institutionCatalogUi['filters'] ?? [];
  $filterCounts = $institutionCatalogUi['counts'] ?? [];
  $sectionKey = $institutionCatalogUi['key'] ?? $activeSection;
?>

<div class="institution-catalog-workspace-header"
     data-institution-catalog-shell="<?php echo e($sectionKey); ?>"
     data-institution-carousel-mode="<?php echo e($institutionCatalogUi['carousel_mode'] ?? 'shared'); ?>"
     data-institution-catalog-singular="<?php echo e($institutionCatalogUi['singular']); ?>"
     data-institution-catalog-plural="<?php echo e($institutionCatalogUi['plural']); ?>">
  <section class="institution-service-carousel-card institution-catalog-carousel-card">
    <button type="button"
            class="institution-service-carousel-arrow"
            data-institution-carousel-prev
            aria-label="Filtro anterior">&lsaquo;</button>

    <div class="institution-service-carousel institution-catalog-carousel"
         data-institution-carousel
         role="group"
         aria-label="Filtros de <?php echo e(strtolower($institutionCatalogUi['title'])); ?>">
      <?php $__currentLoopData = $carouselItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterValue => [$label, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button"
                class="<?php echo \Illuminate\Support\Arr::toCssClasses(['institution-service-carousel-button', 'is-active' => $filterValue === 'all']); ?>"
                data-institution-carousel-filter="<?php echo e($filterValue); ?>"
                aria-pressed="<?php echo e($filterValue === 'all' ? 'true' : 'false'); ?>">
          <span class="institution-service-carousel-icon" aria-hidden="true">
            <?php switch($icon):
              case ('catalog'): ?>
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <?php break; ?>
              <?php case ('maintenance'): ?>
                <svg viewBox="0 0 24 24"><path d="m14.7 6.3 3-3a4 4 0 0 1-5 5l-7 7a2 2 0 0 0 2.8 2.8l7-7a4 4 0 0 1 5-5l-3 3"/><path d="m3 21 5-5"/></svg>
                <?php break; ?>
              <?php case ('inactive'): ?>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6 18.4 18.4"/></svg>
                <?php break; ?>
              <?php default: ?>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
            <?php endswitch; ?>
          </span>
          <strong><?php echo e($label); ?></strong>
        </button>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <button type="button"
            class="institution-service-carousel-arrow"
            data-institution-carousel-next
            aria-label="Filtro siguiente">&rsaquo;</button>
  </section>

  <section class="institution-catalog-summary-card">
    <div class="institution-catalog-summary-copy">
      <h1><?php echo e($institutionCatalogUi['title']); ?></h1>
      <p><?php echo e($institutionCatalogUi['description']); ?></p>
      <div class="institution-catalog-summary-meta">
        <span class="institution-native-status">Activo</span>
        <span data-institution-visible-summary><?php echo e($institutionCatalogUi['count']); ?> <?php echo e($institutionCatalogUi['count'] === 1 ? $institutionCatalogUi['singular'] : $institutionCatalogUi['plural']); ?></span>
      </div>
    </div>
  </section>

  <div class="institution-catalog-toolbar">
    <div class="institution-catalog-filter-buttons" role="group" aria-label="Filtrar <?php echo e(strtolower($institutionCatalogUi['title'])); ?>">
      <?php $__currentLoopData = $filterItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterValue => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button"
                class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $filterValue === 'all']); ?>"
                data-institution-toolbar-filter="<?php echo e($filterValue); ?>"
                aria-pressed="<?php echo e($filterValue === 'all' ? 'true' : 'false'); ?>">
          <span><?php echo e($label); ?></span>
          <?php if(array_key_exists($filterValue, $filterCounts)): ?>
            <b><?php echo e($filterCounts[$filterValue]); ?></b>
          <?php endif; ?>
        </button>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="institution-catalog-toolbar-actions">
      <label class="institution-catalog-search">
        <span class="sr-only">Buscar en <?php echo e(strtolower($institutionCatalogUi['title'])); ?></span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input type="search"
               data-institution-catalog-search
               placeholder="<?php echo e($institutionCatalogUi['search']); ?>">
      </label>

      <?php switch($institutionCatalogUi['action'] ?? null):
        case ('create-unit'): ?>
          <a class="institution-catalog-primary-action" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'create-unit'])); ?>">
            <span aria-hidden="true">+</span> Nueva unidad
          </a>
          <?php break; ?>
        <?php case ('create-subunit'): ?>
          <button type="button" class="institution-catalog-primary-action" data-create-subunit>
            <span aria-hidden="true">+</span> Nueva subunidad
          </button>
          <?php break; ?>
        <?php case ('create-medication'): ?>
          <button type="button" class="institution-catalog-primary-action" data-show-medication-form>
            <span aria-hidden="true">+</span> Nuevo medicamento
          </button>
          <?php break; ?>
        <?php case ('create-specialty'): ?>
          <button type="button" class="institution-catalog-primary-action" data-show-specialty-form>
            <span aria-hidden="true">+</span> Nueva especialidad
          </button>
          <?php break; ?>
      <?php endswitch; ?>
    </div>
  </div>
</div>

<script>
  (() => {
    const initialize = () => {
      const root = document.querySelector('[data-institution-catalog-shell="<?php echo e($sectionKey); ?>"]');
      if (! root) return;

      const normalize = (value) => (value ?? '')
        .toLocaleLowerCase('es')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
      const carousel = root.querySelector('[data-institution-carousel]');
      const carouselButtons = [...root.querySelectorAll('[data-institution-carousel-filter]')];
      const toolbarButtons = [...root.querySelectorAll('[data-institution-toolbar-filter]')];
      const search = root.querySelector('[data-institution-catalog-search]');
      const summary = root.querySelector('[data-institution-visible-summary]');
      const carouselUsesGroups = root.dataset.institutionCarouselMode === 'groups';
      let activeCarouselFilter = 'all';
      let activeToolbarFilter = 'all';

      const rows = () => [...document.querySelectorAll('[data-institution-catalog-row="<?php echo e($sectionKey); ?>"]')];
      const setActiveButtons = () => {
        carouselButtons.forEach((button) => {
          const active = button.dataset.institutionCarouselFilter === activeCarouselFilter;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        toolbarButtons.forEach((button) => {
          const active = button.dataset.institutionToolbarFilter === activeToolbarFilter;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
      };
      const applyFilters = () => {
        const term = normalize(search?.value);
        let visible = 0;

        rows().forEach((row) => {
          const status = row.dataset.institutionCatalogStatus ?? 'active';
          const groups = (row.dataset.institutionCatalogGroups ?? '').split(/\s+/).filter(Boolean);
          const searchValue = normalize(row.dataset.institutionCatalogSearch || row.textContent);
          const carouselMatches = activeCarouselFilter === 'all'
            || (carouselUsesGroups ? groups.includes(activeCarouselFilter) : status === activeCarouselFilter);
          const statusMatches = activeToolbarFilter === 'all' || status === activeToolbarFilter;
          const localMatches = row.dataset.institutionLocalMatch !== 'false';
          const matches = carouselMatches && statusMatches && localMatches && (! term || searchValue.includes(term));
          row.hidden = ! matches;
          if (matches) visible += 1;
        });

        if (summary) {
          const label = visible === 1 ? root.dataset.institutionCatalogSingular : root.dataset.institutionCatalogPlural;
          summary.textContent = `${visible} ${label}`;
        }
        document.dispatchEvent(new CustomEvent('institution:catalog-applied', { detail: { section: '<?php echo e($sectionKey); ?>', visible } }));
      };
      const selectCarouselFilter = (value) => {
        activeCarouselFilter = value;
        if (! carouselUsesGroups) activeToolbarFilter = value;
        setActiveButtons();
        applyFilters();
      };
      const selectToolbarFilter = (value) => {
        activeToolbarFilter = value;
        if (! carouselUsesGroups) activeCarouselFilter = value;
        setActiveButtons();
        applyFilters();
      };

      carouselButtons.forEach((button) => button.addEventListener('click', () => selectCarouselFilter(button.dataset.institutionCarouselFilter)));
      toolbarButtons.forEach((button) => button.addEventListener('click', () => selectToolbarFilter(button.dataset.institutionToolbarFilter)));
      search?.addEventListener('input', applyFilters);
      document.addEventListener('institution:catalog-local-filter', applyFilters);
      root.querySelector('[data-institution-carousel-prev]')?.addEventListener('click', () => carousel?.scrollBy({ left: -320, behavior: 'smooth' }));
      root.querySelector('[data-institution-carousel-next]')?.addEventListener('click', () => carousel?.scrollBy({ left: 320, behavior: 'smooth' }));
      applyFilters();
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
      initialize();
    }
  })();
</script>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/institution/_catalog_workspace_header.blade.php ENDPATH**/ ?>