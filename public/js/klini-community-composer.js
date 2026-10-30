/* Live, local preview for the existing community event form. */
(function (global) {
  'use strict';

  function imageUrl(value) {
    const url = String(value || '').trim();
    if (/^data:image\/(png|jpeg|webp|gif|avif);base64,[a-z0-9+/=\s]+$/i.test(url)) return url;
    if (/^(https?:\/\/|\/(?!\/))/i.test(url) && !/[<>\x00-\x20]/.test(url)) return url;
    return '';
  }

  function previewData(values, communityName) {
    const text = key => String(values[key] || '').trim();
    const date = /^\d{4}-\d{2}-\d{2}$/.test(text('date')) ? new Date(text('date') + 'T12:00:00') : null;
    const cost = Number(text('cost'));
    const capacity = Number(text('capacity'));
    const words = (communityName || 'Tu comunidad').split(/\s+/).filter(Boolean);
    const modality = { in_person: 'Presencial', remote: 'Virtual', mixed: 'Mixto' }[text('modality')] || 'Presencial';
    return {
      community: communityName || 'Tu comunidad',
      initials: [words[0], ...(words.length > 1 ? [words.at(-1)] : [])].map(word => word[0]).join('').toUpperCase(),
      title: text('title') || 'Nombre de tu evento',
      shortDescription: text('shortDescription') || 'La descripción de tu evento aparecerá aquí.',
      description: text('description'),
      category: text('category') || 'Wellbeing',
      date: date && !Number.isNaN(date.getTime()) ? date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Fecha por definir',
      time: [text('startTime'), text('endTime')].filter(Boolean).join(' – ') || 'Horario por definir',
      timezone: text('timezone').replaceAll('_', ' '),
      modality,
      location: modality === 'Virtual' ? 'Sala virtual Klini' : text('location') || 'Ubicación por definir',
      capacity: Number.isFinite(capacity) && capacity > 0 ? `${capacity} lugares` : 'Cupo por definir',
      cost: Number.isFinite(cost) && cost > 0 ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cost) + ' MXN' : 'Gratuito',
      image: imageUrl(text('imageUrl'))
    };
  }

  let dispose = null;
  let opener = null;
  function activityData(values, communityName) {
    return {
      community_key: String(values.communityId || ''), community_name: communityName,
      kind: 'event', title: String(values.title || '').trim(),
      description: String(values.shortDescription || '').trim(), image: imageUrl(values.imageUrl),
      starts_at: values.date && values.startTime ? `${values.date}T${values.startTime}` : '',
      timezone: values.timezone || 'America/Mexico_City', cost: Number(values.cost || 0),
      capacity: Number(values.capacity || 20), published: true
    };
  }

  function phoneDocument(styles) {
    const escape = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const tabs = [['01_Inicio','Inicio'],['02_Comunidades','Comunidades'],['03_Registro','Registro'],['04_Salud','Mi Salud'],['05_Calendario','Calendario']];
    return `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${styles.map(url => `<link rel="stylesheet" href="${escape(url)}">`).join('')}</head>
      <body class="native-shell"><header class="patient-assistant-native-topbar patient-portal-topbar" inert>
        <button class="patient-portal-dots" type="button" aria-label="Opciones">⋮</button>
        <div class="patient-portal-question"><input aria-label="Buscar en Klini" readonly></div>
        <button class="patient-portal-profile-trigger" type="button"><span class="patient-portal-avatar">CB</span></button>
      </header><main class="patient-portal-communities-view"><div class="klini-communities"><div class="kc-feed-wrap kc-community-feed" data-preview-content><div data-preview-stories></div><div data-preview-card></div></div></div></main>
      <nav class="patient-portal-bottom-nav" aria-label="Navegación de la aplicación" inert>${tabs.map(([file,label], index) => `<button type="button" class="${index === 1 ? 'patient-bottom-communities is-active' : index === 2 ? 'patient-bottom-register' : ''}" aria-label="${label}"><span class="patient-bottom-png"><img src="/images/patient-bottom-nav/${file}.png" alt=""></span></button>`).join('')}</nav></body></html>`;
  }

  function mount(root) {
    if (dispose) { dispose(); dispose = null; }
    const form = root.querySelector('[data-wellness-event-form]');
    if (!form) {
      const trigger = opener && root.querySelector(opener);
      trigger?.focus({ preventScroll: true });
      opener = null;
      return;
    }
    const sheet = form.closest('.wellness-event-create-sheet');
    const backdrop = sheet.closest('.wellness-modal-backdrop');
    const header = sheet.querySelector('.wellness-event-create-header');
    const close = sheet.querySelector('[data-wellness-close-modal]');
    const footer = form.querySelector('.wellness-event-form-actions');
    const body = document.createElement('div');
    const preview = document.createElement('aside');
    backdrop.classList.add('community-composer-backdrop');
    sheet.classList.add('community-composer');
    body.className = 'community-composer-body';
    preview.className = 'community-composer-preview';
    preview.id = 'communityEventPreview';
    preview.setAttribute('aria-label', 'Vista previa móvil de la publicación');
    close.textContent = '×';
    form.id = 'communityEventForm';
    footer.querySelector('[type="submit"]').setAttribute('form', form.id);
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'wellness-secondary-action community-composer-toggle';
    toggle.textContent = 'Vista previa móvil';
    toggle.setAttribute('aria-controls', preview.id);
    toggle.setAttribute('aria-pressed', 'false');
    toggle.addEventListener('click', () => {
      const visible = sheet.classList.toggle('show-mobile-preview');
      toggle.setAttribute('aria-pressed', String(visible));
      toggle.textContent = visible ? 'Volver al formulario' : 'Vista previa móvil';
    });
    footer.prepend(toggle);
    preview.innerHTML = `
      <div class="community-preview-phone">
        <div class="community-phone-status" aria-hidden="true"><span>9:41</span><i></i><span>▮▮▮ ▰</span></div>
        <div class="community-phone-screen"><iframe title="Publicación del evento en la aplicación pública de Klini" sandbox="allow-same-origin"></iframe></div>
      </div>
      <button type="button" class="community-share-trigger" aria-label="Publicar en redes sociales" aria-haspopup="dialog" aria-expanded="false">
        <svg class="community-share-symbol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4"/></svg>
        <span class="community-share-label">Publicar en</span>
        <span class="community-share-brands" aria-hidden="true">
          <span class="community-share-brand instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg></span>
          <span class="community-share-brand facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14.5 23V13h3.3l.5-3.8h-3.8V6.8c0-1.1.3-1.8 1.9-1.8h2V1.6c-.4-.1-1.6-.2-2.9-.2-2.9 0-4.8 1.8-4.8 5v2.8H7.5V13h3.2v10z"/></svg></span>
          <span class="community-share-brand tiktok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 3h3c.3 2.1 1.5 3.4 3.5 3.8v3a9.2 9.2 0 0 1-3.5-1v7a5.5 5.5 0 1 1-5.5-5.5h.8v3.1l-.8-.1a2.5 2.5 0 1 0 2.5 2.5z"/></svg></span>
          <span class="community-share-brand x"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 4h4l10 16h-4zM19 4l-6.3 7M5 20l6.3-7"/></svg></span>
        </span>
        <svg class="community-share-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
      </button>`;
    sheet.append(body, footer);
    body.append(preview, form);

    const frame = preview.querySelector('iframe');
    const screen = preview.querySelector('.community-phone-screen');
    const shareTrigger = preview.querySelector('.community-share-trigger');
    const shareDialog = document.createElement('dialog');
    shareDialog.className = 'community-social-dialog';
    shareDialog.setAttribute('aria-labelledby', 'communitySocialTitle');
    const networks = [['klini','Klini','k'],['whatsapp','WhatsApp','W'],['x','X','𝕏'],['telegram','Telegram','➤']];
    shareDialog.innerHTML = `<button type="button" class="community-social-close" aria-label="Cerrar redes sociales">×</button><h3 id="communitySocialTitle">Publicar en redes sociales</h3><div class="community-social-options">${networks.map(([id,label,mark]) => `<button type="button" data-social="${id}"><span class="community-social-icon ${id}" aria-hidden="true">${mark}</span><strong>${label}</strong></button>`).join('')}</div><p class="community-social-status" role="status" aria-live="polite"></p>`;
    sheet.append(shareDialog);
    const shareStatus = shareDialog.querySelector('[role="status"]');
    let publishing = false;
    let published = null;
    const sourceKey = `event-${Date.now()}-${crypto.randomUUID().replaceAll('-', '')}`;
    const submitButton = footer.querySelector('[type="submit"]');
    const formStatus = document.createElement('p');
    formStatus.className = 'community-event-save-status';
    formStatus.setAttribute('role', 'alert');
    form.prepend(formStatus);
    function values() { return Object.fromEntries(new FormData(form)); }
    function communityName() { return form.elements.namedItem('communityId').selectedOptions[0]?.textContent || 'Tu comunidad'; }
    function sizePhone() {
      if (!screen.clientWidth) return;
      const scale = screen.clientWidth / 390;
      frame.style.width = '390px';
      frame.style.height = `${screen.clientHeight / scale}px`;
      frame.style.transform = `scale(${scale})`;
    }
    const observer = new ResizeObserver(sizePhone);
    observer.observe(screen);
    frame.addEventListener('load', () => {
      frame.contentDocument?.addEventListener('keydown', keydown);
      update(); sizePhone();
    });
    frame.srcdoc = phoneDocument(JSON.parse(document.getElementById('communityEventPreviewAssets').textContent));
    function update() {
      const target = frame.contentDocument?.querySelector('[data-preview-card]');
      if (!target) return;
      const data = activityData(values(), communityName());
      const rendered = global.KliniCommunities.renderEventPreview({...data, id:'preview', title:data.title || 'Nombre de tu evento', description:data.description || 'La descripción de tu evento aparecerá aquí.', reservations:0, reserved:false});
      const stories = frame.contentDocument.querySelector('[data-preview-stories]');
      if (!stories.innerHTML) stories.innerHTML = rendered.stories;
      target.innerHTML = rendered.card;
      frame.contentDocument.querySelectorAll('[data-preview-content] button').forEach(button => { button.tabIndex = -1; });
      target.querySelectorAll('img').forEach(img => img.addEventListener('error', () => {
        if (img.dataset.fallbackSrc) { const fallback = img.dataset.fallbackSrc; delete img.dataset.fallbackSrc; img.src = fallback; }
      }, {once:true}));
    }
    shareTrigger.addEventListener('click', () => { shareStatus.textContent = ''; shareDialog.showModal(); shareTrigger.setAttribute('aria-expanded', 'true'); });
    shareDialog.addEventListener('close', () => shareTrigger.setAttribute('aria-expanded', 'false'));
    shareDialog.querySelector('.community-social-close').addEventListener('click', () => shareDialog.close());
    shareDialog.addEventListener('cancel', event => { if (publishing) event.preventDefault(); });
    shareDialog.addEventListener('click', async event => {
      const network = event.target.closest('[data-social]')?.dataset.social;
      if (!network || publishing) return;
      if (!form.checkValidity()) { shareDialog.close(); showInvalidForm(); form.reportValidity(); return; }
      const data = values();
      if (data.endTime <= data.startTime) { shareStatus.textContent = 'La finalización debe ser posterior al inicio.'; return; }
      if (network !== 'klini') {
        const preview = previewData(data, communityName());
        const text = [preview.title, preview.shortDescription, preview.community, `${preview.date} · ${preview.time}`, preview.location, preview.cost].join('\n');
        const encoded = encodeURIComponent(text);
        const urls = {whatsapp:`https://wa.me/?text=${encoded}`,x:`https://twitter.com/intent/tweet?text=${encoded}`,telegram:`https://t.me/share/url?url=&text=${encoded}`};
        window.open(urls[network], '_blank', 'noopener,noreferrer');
        shareStatus.textContent = 'Completa la publicación en la red social seleccionada.';
        return;
      }
      await createEvent();
    });
    async function createEvent() {
      if (publishing) return;
      const completionData = new FormData(form);
      const data = Object.fromEntries(completionData);
      if (data.endTime <= data.startTime) {
        formStatus.textContent = shareStatus.textContent = 'La finalización debe ser posterior al inicio.';
        return;
      }
      publishing = true;
      const controls = [...sheet.querySelectorAll('button,input,select,textarea')];
      const previousDisabled = controls.map(control => control.disabled);
      controls.forEach(control => { control.disabled = true; });
      submitButton.textContent = 'Creando evento…';
      formStatus.textContent = '';
      shareStatus.textContent = 'Publicando en Klini…';
      try {
        if (!published) {
          const activity = activityData(data, communityName());
          const payload = new FormData();
          payload.set('source_key', sourceKey);
          Object.entries(activity).forEach(([key,value]) => { if (key !== 'image') payload.set(key, typeof value === 'boolean' ? Number(value) : value); });
          if (activity.image.startsWith('data:')) {
            const file = form.querySelector('[data-wellness-event-image-file]').files[0];
            if (!file) throw Error('Vuelve a seleccionar la imagen para publicarla.');
            payload.set('image_file', file);
          } else if (activity.image) payload.set('image', activity.image);
          published = await global.KliniCommunityActivities.request('', 'POST', payload);
        }
        completionData.set('sourceKey', sourceKey);
        completionData.set('publicActivityId', published.id);
        if (published.image) completionData.set('imageUrl', published.image);
        shareDialog.close();
        global.KliniWellnessCommunity.completeEventCreation(completionData, published);
      } catch (error) {
        formStatus.textContent = shareStatus.textContent = error.message || 'No se pudo crear el evento. Intenta de nuevo.';
      } finally {
        publishing = false;
        controls.forEach((control, index) => { control.disabled = previousDisabled[index]; });
        submitButton.textContent = 'Crear evento';
      }
    }
    form.addEventListener('submit', event => {
      event.preventDefault();
      event.stopPropagation();
      createEvent();
    });
    function position() {
      const scale = parseFloat(getComputedStyle(document.body).zoom) || 1;
      const horizontalMargin = innerWidth > 760 ? (innerWidth - Math.min(1280, innerWidth * .88)) / 2 : 12;
      const verticalMargin = innerHeight > 600 ? Math.min(64, innerHeight * .075) : 12;
      // DOM rectangles are physical CSS pixels; the application uses body zoom.
      backdrop.style.width = `${innerWidth / scale}px`;
      backdrop.style.height = `${innerHeight / scale}px`;
      sheet.style.left = sheet.style.right = `${horizontalMargin / scale}px`;
      sheet.style.top = sheet.style.bottom = `${verticalMargin / scale}px`;
    }
    function keydown(event) {
      if (publishing) { if (event.key === 'Escape') event.preventDefault(); return; }
      if (shareDialog.open) return;
      if (event.key === 'Escape') { event.preventDefault(); close.click(); return; }
      if (event.key !== 'Tab') return;
      const focusable = [...sheet.querySelectorAll('button,input,textarea,select,iframe,[tabindex="0"]')].filter(el => !el.disabled && el.getClientRects().length);
      const first = focusable[0], last = focusable.at(-1);
      if (event.shiftKey && (document.activeElement === first || !sheet.contains(document.activeElement))) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && (document.activeElement === last || !sheet.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
    }
    function showInvalidForm() {
      sheet.classList.remove('show-mobile-preview');
      toggle.textContent = 'Vista previa móvil';
      toggle.setAttribute('aria-pressed', 'false');
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('klini:event-image-change', update);
    form.addEventListener('invalid', showInvalidForm, true);
    document.addEventListener('keydown', keydown);
    window.addEventListener('resize', position);
    document.documentElement.classList.add('community-composer-open');
    update(); position();
    form.elements.namedItem('title').focus({ preventScroll: true });
    dispose = () => {
      document.documentElement.classList.remove('community-composer-open');
      observer.disconnect();
      shareDialog.remove();
      document.removeEventListener('keydown', keydown);
      window.removeEventListener('resize', position);
    };
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = { previewData, imageUrl, activityData, phoneDocument };
  else {
    global.KliniEventComposer = { mount };
    document.addEventListener('click', event => {
      if (event.target.closest('[data-wellness-modal="create-event"]')) opener = '[data-wellness-modal="create-event"]';
    }, true);
  }
})(typeof window === 'undefined' ? {} : window);
