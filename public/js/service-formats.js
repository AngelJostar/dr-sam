(() => {
    const initializePrescription = root => {
        root.querySelectorAll('.superadmin-native-prescription-paper').forEach(paper => {
            const medications = paper.querySelector('[data-prescription-medications]');
            const addButton = paper.querySelector('[data-add-medication]');
            const rowTemplate = medications?.querySelector('[data-prescription-medication]')?.cloneNode(true);
            if (!rowTemplate || !addButton) return;
            const renumber = () => medications.querySelectorAll('[data-prescription-medication]').forEach((row, index) => {
                row.querySelector('[data-medication-number]').textContent = index + 1;
                row.querySelectorAll('[data-medication-field]').forEach(field => {
                    field.name = `medications[${index}][${field.dataset.medicationField}]`;
                });
            });
            medications.addEventListener('click', event => {
                const remove = event.target.closest('[data-remove-medication]');
                if (!remove) return;
                remove.closest('[data-prescription-medication]').remove();
                renumber();
            });
            addButton.addEventListener('click', () => {
                const row = rowTemplate.cloneNode(true);
                row.querySelectorAll('input, textarea').forEach(field => field.value = '');
                medications.appendChild(row);
                renumber();
                row.querySelector('input, textarea')?.focus();
            });
        });
    };
    initializePrescription(document);

    const dialog = document.getElementById('service-formats-dialog');
    if (!dialog) return;
    const preview = document.getElementById('service-format-preview-dialog');
    const previewContent = preview.querySelector('[data-service-format-preview-content]');
    let opener;
    let formatOpener;
    document.querySelectorAll('[data-service-formats]').forEach(button => {
        button.addEventListener('click', () => {
            const template = document.getElementById(`service-formats-${button.dataset.serviceFormats}`);
            if (!template) return;
            opener = button;
            dialog.querySelector('#service-formats-name').textContent = button.dataset.serviceName;
            dialog.querySelector('[data-service-formats-content]').replaceChildren(template.content.cloneNode(true));
            dialog.showModal();
        });
    });
    dialog.querySelectorAll('[data-close-service-formats]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => opener?.focus({preventScroll: true}));

    dialog.addEventListener('click', event => {
        const link = event.target.closest('[data-open-service-format]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const template = document.getElementById(`service-format-preview-${link.dataset.openServiceFormat}`);
        if (!template) return;
        event.preventDefault();
        formatOpener = link;
        preview.querySelector('#service-format-preview-title').textContent = link.dataset.formatName;
        preview.querySelector('#service-format-preview-service').textContent = dialog.querySelector('#service-formats-name').textContent;
        previewContent.replaceChildren(template.content.cloneNode(true));
        initializePrescription(previewContent);
        preview.showModal();
        previewContent.scrollTop = 0;
    });
    preview.querySelectorAll('[data-close-service-format-preview]').forEach(button => button.addEventListener('click', () => preview.close()));
    preview.addEventListener('close', () => {
        previewContent.replaceChildren();
        formatOpener?.focus({preventScroll: true});
    });
})();
