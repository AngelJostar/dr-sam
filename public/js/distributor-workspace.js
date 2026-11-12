(() => {
    if (!document.body.classList.contains('distributor-body')) return;
    const workspace = document.querySelector('.superadmin-native-workspace');
    const statusScrollKey = 'distributor-status-scroll:' + location.pathname;
    try {
        const top = sessionStorage.getItem(statusScrollKey);
        if (top !== null) workspace.scrollTop = Number(top);
        sessionStorage.removeItem(statusScrollKey);
    } catch (_) {}
    document.querySelectorAll('[data-distributor-status-form]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.submitting) { event.preventDefault(); return; }
            form.dataset.submitting = 'true';
            const button = form.querySelector('[role="switch"]');
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            try { sessionStorage.setItem(statusScrollKey, String(workspace.scrollTop)); } catch (_) {}
        });
    });
    const positions = new WeakMap();
    const open = (dialog, trigger) => {
        if (!dialog || dialog.open) return;
        positions.set(dialog, { top: workspace.scrollTop, trigger });
        dialog.showModal();
    };
    document.querySelectorAll('[data-open-distributor-dialog]').forEach(trigger => {
        trigger.addEventListener('click', event => {
            event.preventDefault();
            open(document.getElementById(trigger.dataset.openDistributorDialog), trigger);
        });
    });
    const createTile = document.querySelector('[data-admin-filter="create"]');
    if (document.getElementById('distributor-product-new')) {
        createTile?.addEventListener('click', event => {
            event.preventDefault();
            open(document.getElementById('distributor-product-new'), createTile);
        });
    }
    document.querySelectorAll('.distributor-dialog').forEach(dialog => {
        dialog.querySelectorAll('[data-close-distributor-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('close', () => {
            const position = positions.get(dialog);
            position?.trigger?.focus({ preventScroll: true });
            if (position) workspace.scrollTop = position.top;
            if (dialog.dataset.publicationKey) {
                try { sessionStorage.setItem('klini-distributor-notice-' + dialog.dataset.publicationKey, 'seen'); } catch (_) {}
            }
        });
    });
    const autoOpen = document.querySelector('[data-distributor-auto-open]');
    if (autoOpen) open(autoOpen);
    else {
        const notice = document.querySelector('[data-show-publication]');
        if (notice) {
            let seen = false;
            try { seen = sessionStorage.getItem('klini-distributor-notice-' + notice.dataset.publicationKey) === 'seen'; } catch (_) {}
            if (!seen) open(notice);
        }
    }
    document.querySelectorAll('.distributor-group-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(expanded));
            document.getElementById(button.getAttribute('aria-controls')).hidden = !expanded;
        });
    });
    document.querySelectorAll('[data-dismiss-flash]').forEach(button => button.addEventListener('click', () => button.closest('.distributor-flash').remove()));
})();
