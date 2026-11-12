<div class="drsam-table-filter-head">
    <span class="drsam-table-filter-label">{{ $label }}</span>
    <button type="button" @class(['drsam-table-filter-button', 'is-active' => array_key_exists($column, $columnFilters['selected'])])
        data-distributor-column="{{ $column }}" data-column-options="{{ json_encode($columnFilters['columns'][$column], JSON_UNESCAPED_UNICODE) }}"
        aria-label="Filtrar {{ $label }}" aria-haspopup="dialog" aria-expanded="false"
        aria-pressed="{{ array_key_exists($column, $columnFilters['selected']) ? 'true' : 'false' }}"></button>
</div>
