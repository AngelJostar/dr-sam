(() => {
    if (!document.body.classList.contains('distributor-body')) return;
    const workspace = document.querySelector('.superadmin-native-workspace');
    const scrollKey = 'distributor-column-scroll:' + location.pathname;
    try {
        const top = sessionStorage.getItem(scrollKey);
        if (top !== null) workspace.scrollTop = Number(top);
        sessionStorage.removeItem(scrollKey);
    } catch (_) {}

    let active = null;
    const close = (restoreFocus = false) => {
        if (!active) return;
        const { menu, button, cleanup } = active;
        cleanup();
        menu.remove();
        button.setAttribute('aria-expanded', 'false');
        active = null;
        if (restoreFocus) button.focus({ preventScroll: true });
    };
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');

    document.querySelectorAll('[data-distributor-column]').forEach(button => {
        button.addEventListener('click', () => {
            if (active?.button === button) { close(true); return; }
            close();
            const config = JSON.parse(button.dataset.columnOptions);
            const selected = new Set(config.selected ?? config.options.map(option => option.value));
            const menu = document.createElement('div');
            menu.className = 'drsam-table-filter-menu distributor-column-menu';
            menu.id = 'distributor-filter-' + button.dataset.distributorColumn;
            menu.setAttribute('role', 'dialog');
            menu.setAttribute('aria-label', button.getAttribute('aria-label'));
            const search = document.createElement('input');
            search.type = 'search';
            search.className = 'drsam-table-filter-search';
            search.placeholder = 'Buscar (Todos)';
            search.setAttribute('aria-label', 'Buscar valores de la columna');
            const options = document.createElement('div');
            options.className = 'drsam-table-filter-options';
            const option = (text, checked) => {
                const label = document.createElement('label');
                label.className = 'drsam-table-filter-option';
                label.title = text;
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = checked;
                const caption = document.createElement('span');
                caption.textContent = text;
                label.append(input, caption);
                options.append(label);
                return { label, input };
            };
            const all = option('(Todos)', false);
            all.label.classList.add('is-all');
            const choices = config.options.map(value => ({ ...value, ...option(value.label, selected.has(value.value)) }));
            const updateAll = () => {
                all.input.checked = choices.length > 0 && choices.every(choice => choice.input.checked);
                all.input.indeterminate = !all.input.checked && choices.some(choice => choice.input.checked);
            };
            // The search narrows the list without silently changing the selection.
            all.input.addEventListener('change', () => {
                choices.forEach(choice => { choice.input.checked = all.input.checked; });
                updateAll();
            });
            choices.forEach(choice => choice.input.addEventListener('change', updateAll));
            updateAll();
            const empty = document.createElement('p');
            empty.className = 'distributor-column-empty';
            empty.setAttribute('role', 'status');
            empty.textContent = 'No hay valores disponibles.';
            empty.hidden = choices.length > 0;
            options.append(empty);
            all.label.hidden = !choices.length;
            search.disabled = !choices.length;
            search.addEventListener('input', () => {
                const term = normalize(search.value.trim());
                choices.forEach(choice => { choice.label.hidden = !normalize(choice.label.title).includes(term); });
                empty.textContent = 'Sin coincidencias.';
                empty.hidden = choices.some(choice => !choice.label.hidden);
            });
            const footer = document.createElement('div');
            footer.className = 'drsam-table-filter-footer';
            const accept = document.createElement('button');
            accept.type = 'button';
            accept.textContent = 'Aceptar';
            accept.disabled = !choices.length;
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.textContent = 'Cancelar';
            cancel.addEventListener('click', () => close(true));
            accept.addEventListener('click', () => {
                const url = new URL(location.href);
                const filters = JSON.parse(url.searchParams.get('columns') || '{}');
                const values = choices.filter(choice => choice.input.checked).map(choice => choice.value);
                if (values.length === choices.length) delete filters[button.dataset.distributorColumn];
                else filters[button.dataset.distributorColumn] = values;
                if (Object.keys(filters).length) url.searchParams.set('columns', JSON.stringify(filters));
                else url.searchParams.delete('columns');
                ['page', 'edit'].forEach(key => url.searchParams.delete(key));
                try { sessionStorage.setItem(scrollKey, String(workspace.scrollTop)); } catch (_) {}
                location.assign(url.href);
            });
            footer.append(accept, cancel);
            menu.append(search, options, footer);
            document.body.append(menu);
            button.setAttribute('aria-expanded', 'true');
            button.setAttribute('aria-controls', menu.id);

            const position = () => {
                const scale = Number(getComputedStyle(document.body).zoom) || 1;
                const viewportWidth = window.innerWidth / scale;
                const viewportHeight = window.innerHeight / scale;
                menu.style.width = Math.min(280, viewportWidth - 16) + 'px';
                menu.style.maxHeight = Math.min(330, viewportHeight - 16) + 'px';
                const rect = button.getBoundingClientRect();
                menu.style.left = Math.max(8, Math.min(rect.right / scale - menu.offsetWidth, viewportWidth - menu.offsetWidth - 8)) + 'px';
                const below = rect.bottom / scale + 6;
                const top = below + menu.offsetHeight <= viewportHeight - 8 ? below : rect.top / scale - menu.offsetHeight - 6;
                menu.style.top = Math.max(8, top) + 'px';
            };
            position();
            const outside = event => { if (!menu.contains(event.target) && !button.contains(event.target)) close(); };
            const keydown = event => { if (event.key === 'Escape') { event.preventDefault(); close(true); } };
            const scroll = event => { if (!menu.contains(event.target)) close(); };
            const focus = event => { if (!menu.contains(event.target) && event.target !== button) close(); };
            document.addEventListener('pointerdown', outside, true);
            document.addEventListener('keydown', keydown, true);
            document.addEventListener('focusin', focus);
            window.addEventListener('scroll', scroll, true);
            window.addEventListener('resize', position);
            active = { menu, button, cleanup: () => {
                document.removeEventListener('pointerdown', outside, true);
                document.removeEventListener('keydown', keydown, true);
                document.removeEventListener('focusin', focus);
                window.removeEventListener('scroll', scroll, true);
                window.removeEventListener('resize', position);
            } };
            (search.disabled ? cancel : search).focus({ preventScroll: true });
        });
    });
})();
