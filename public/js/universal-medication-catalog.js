(() => {
    const dialog = document.getElementById('medication-identifiers-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('[data-medication-form]');
    const open = (button, old = null) => {
        const medication = JSON.parse(button.dataset.medication);
        form.action = medication.action;
        form.elements.identifier_id.value = medication.id;
        for (const field of ['sku', 'barcode', 'barcode_type']) {
            form.elements[field].value = (old ? old[field] : medication[field]) ?? (field === 'barcode_type' ? 'internal' : '');
        }
        dialog.querySelector('[data-medication-caption]').textContent = `${medication.name} · ${medication.cnis || 'Sin CNIS'}`;
        dialog.showModal();
    };
    document.querySelectorAll('[data-medication]').forEach(button => button.addEventListener('click', () => open(button)));
    dialog.querySelectorAll('[data-close-medication]').forEach(button => button.addEventListener('click', () => dialog.close()));
    if (dialog.dataset.reopenId) {
        const button = [...document.querySelectorAll('[data-medication-id]')].find(button => button.dataset.medicationId === dialog.dataset.reopenId);
        if (button) open(button, JSON.parse(dialog.dataset.oldIdentifiers));
    }
    const createDialog = document.getElementById('new-medication-dialog');
    if (createDialog) {
        document.querySelectorAll('[data-new-medication]').forEach(button => button.addEventListener('click', () => createDialog.showModal()));
        createDialog.querySelectorAll('[data-close-new-medication]').forEach(button => button.addEventListener('click', () => createDialog.close()));
        if (createDialog.dataset.hasErrors === 'true') createDialog.showModal();
        createDialog.querySelector('form').addEventListener('submit', () => {
            const button = createDialog.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Guardando…';
        });
    }
})();

(() => {
    const dialog = document.getElementById('medication-status-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    let trigger;
    const openStatus = (button, old = null) => {
        trigger = button;
        const item = JSON.parse(button.dataset.statusMedication);
        form.action = item.action;
        form.elements.status_medication_id.value = item.id;
        form.elements.status.value = old || item.status;
        dialog.querySelector('[data-status-caption]').textContent = `${item.name} · SKU ${item.sku || 'por registrar'}`;
        dialog.showModal();
    };
    document.querySelectorAll('[data-status-medication]').forEach(button => {
        button.addEventListener('click', () => openStatus(button));
        if (String(JSON.parse(button.dataset.statusMedication).id) === dialog.dataset.reopenStatus) openStatus(button, dialog.dataset.oldStatus);
    });
    dialog.querySelectorAll('[data-close-status]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => trigger?.focus({preventScroll: true}));
    form.addEventListener('submit', () => { form.querySelector('[type=submit]').disabled = true; });

    document.querySelectorAll('[data-medication-publication]').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('[role=switch]');
        if (button.disabled) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const feedback = document.querySelector('.medication-publication-feedback');
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error('No se pudo guardar. Recarga la página e inténtalo de nuevo.');
            const result = await response.json();
            button.setAttribute('aria-checked', String(result.enabled));
            button.querySelector('b').textContent = result.enabled ? 'ON' : 'OFF';
            form.elements.digital_pharmacy_enabled.value = result.enabled ? '0' : '1';
            feedback.textContent = result.message;
            feedback.classList.remove('is-error');
        } catch (error) {
            feedback.textContent = error.message;
            feedback.classList.add('is-error');
        } finally {
            feedback.hidden = false;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.focus({preventScroll: true});
        }
    }));
})();
