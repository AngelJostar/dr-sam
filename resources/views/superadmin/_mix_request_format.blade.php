@php
  $oncologyMedicines = ['Ciclofosfamida', 'Ifosfamida', 'Cisplatino', 'Carboplatino', 'Oxaliplatino', 'Paclitaxel', 'Docetaxel', 'Doxorubicina', 'Epirubicina', 'Fluorouracilo 5-FU', 'Metotrexato', 'Gemcitabina', 'Citarabina', 'Vincristina', 'Vinblastina', 'Etoposido', 'Irinotecan', 'Rituximab', 'Trastuzumab', 'Pembrolizumab', 'Nivolumab', 'Ondansetron', 'Dexametasona'];
  $nptProducts = [
    'Aminoacidos estandar al 10% g/kg', 'Aminoacidos pediatricos al 10% g/kg', 'Aminoacidos al 8% de cadena ramificada g/kg',
    'Aminoacidos para nefropatas 5.4% g/kg', 'Solucion glucosada al 50% g/kg', 'Lipidos de cadena media y larga al 20% g/kg',
    'Lipidos de cadena media con acidos grasos Omega 3 al 20% g/kg', 'Cloruro de sodio 17.7% (3 mEq/ml) mEq/kg', 'Acetato de potasio (2 mEq/ml) mEq/kg',
    'Acetato de sodio (4 mEq/ml) mEq/kg', 'Fosfato de potasio (2 mEq/ml) mEq/kg', 'Sulfato de magnesio (0.81 mEq/ml) mEq/kg',
    'Gluconato de calcio (0.465 mEq/ml) mEq/kg', 'Cloruro de potasio (4 mEq/ml) mEq/kg', 'Emulsion de aceite de pescado al 10% (AG Omega 3) ml',
    'Acido folinico (12.5 mg/ml) mg', 'Albumina 25% (0.25 g/ml) g', 'Vitamina C (100 mg/ml) mg',
    'Glutamina 20% g', 'Vitamina K (10 mg/ml) mg', 'Cromo (4 mcg/ml) mcg',
    'Zinc (1 mg/ml) mg', 'Heparina (1000 UI/ml) UI', 'L-Cisteina (50 mg/ml) mg',
    'L-Carnitina (200 mg/ml) mg', 'Insulina (100 UI/ml) UI', 'Multivitaminico ml',
    'Multivitaminico pediatrico ml', 'Oligoelementos ml',
  ];
@endphp

<section class="superadmin-mix-selector" data-mix-selector>
  <header><h2>Formato de solicitud de mezcla</h2><p>Selecciona el formato base que se usara en Modulo medico y Modulo de area operativa.</p></header>
  <div>
    <button type="button" data-open-mix="oncology"><strong>Mezcla oncologica</strong><span>Abre el formato de solicitud de quimioterapia copiado del proveedor integral.</span></button>
    <button type="button" data-open-mix="parenteral"><strong>Nutricion parenteral</strong><span>Abre el formato de solicitud de NPT copiado del proveedor integral.</span></button>
  </div>
</section>

<section class="superadmin-mix-format hidden" data-mix-panel="oncology">
  <header class="superadmin-mix-format-toolbar"><div><h2>Formato de solicitud de servicio</h2><p>Solicitud de Oncologia / Quimioterapia</p></div><button type="button" data-close-mix>&larr; Atras</button></header>
  <form class="superadmin-oncology-form" onsubmit="event.preventDefault()">
    <h2>SOLICITUD DE ONCOLOGICOS</h2>
    <div class="superadmin-oncology-body">
      <div class="superadmin-oncology-fields">
        <label>Institucion<em>*</em><input required placeholder="Institucion solicitante"></label><label>Unidad<em>*</em><input required placeholder="Unidad solicitante"></label><label>Servicio<em>*</em><input required value="Oncologia medica"></label>
        <label>Fecha<em>*</em><input required type="date"></label><label>Piso<input></label><label>Cama<input></label>
        <label class="is-span-2">Nombre del Paciente<em>*</em><input required></label><label>ID del paciente<input placeholder="ID del paciente"></label>
        <label>ID de la plataforma<input></label><fieldset><legend>Sexo</legend><label><input type="radio" name="oncology_sex" value="F"> F</label><label><input type="radio" name="oncology_sex" value="M"> M</label></fieldset><label>Edad<input type="number" min="0"></label>
        <label>Peso<input type="number" min="0" step="0.01"></label><label>Fecha de Nacimiento<input type="date"></label><label>Sup. Corporal<input type="number" min="0" step="0.01"></label>
        <label class="is-span-2">Diagnostico<em>*</em><input required></label>
      </div>
      <div class="superadmin-oncology-table-wrap"><table class="superadmin-oncology-table">
        <thead><tr><th rowspan="2">No.</th><th rowspan="2">Medicamento*</th><th rowspan="2">Dosis*</th><th colspan="3">Diluyente*</th><th rowspan="2">Volumen de dilucion total ml*</th><th rowspan="2">No. de bolos x dia*</th><th rowspan="2">Tiempo de infusion (min)</th><th colspan="4">Via de administracion*</th><th colspan="6">Fecha de entrega*</th></tr><tr><th>CS</th><th>DX</th><th>Otro</th><th>IV</th><th>IM</th><th>SC</th><th>Otro</th>@for ($date = 1; $date <= 6; $date++)<th>Fecha {{ $date }}</th>@endfor</tr></thead>
        <tbody>@for ($row = 1; $row <= 8; $row++)<tr><th>{{ $row }}</th><td><select aria-label="Medicamento renglon {{ $row }}"><option value="">Seleccionar</option>@foreach ($oncologyMedicines as $medicine)<option>{{ $medicine }}</option>@endforeach</select></td><td><input aria-label="Dosis renglon {{ $row }}"></td>@for ($check = 1; $check <= 3; $check++)<td><input type="checkbox" aria-label="Diluyente {{ $check }} renglon {{ $row }}"></td>@endfor<td><input aria-label="Volumen renglon {{ $row }}"></td><td><input aria-label="Bolos renglon {{ $row }}"></td><td><input aria-label="Tiempo renglon {{ $row }}"></td>@for ($check = 1; $check <= 4; $check++)<td><input type="checkbox" aria-label="Via {{ $check }} renglon {{ $row }}"></td>@endfor @for ($date = 1; $date <= 6; $date++)<td class="is-date"><input type="date" aria-label="Fecha {{ $date }} renglon {{ $row }}"></td>@endfor</tr>@endfor</tbody>
      </table></div>
      <label class="superadmin-oncology-observations">Observaciones:<em>**</em><textarea></textarea></label>
      <div class="superadmin-oncology-footer"><aside><strong>*Campos obligatorios</strong><p>CS: Cloruro de Sodio 0.9% = Sol. Fisiologica</p><p>DX: Dextrosa 5% = Sol. Glucosada 5%</p><p>IV: Intravenosa</p><p>IM: Intramuscular</p><p>SC: Subcutanea</p></aside><div><label>Horario de infusion preferido por el medico/paciente<input type="time"></label><label>Nombre del medico<em>*</em><input required></label><label>Firma y cedula<input></label><label class="superadmin-oncology-file">+<strong>Adjuntar carta de autorizacion de aseguradora</strong><input type="file"></label></div></div>
      <button class="superadmin-oncology-save" type="submit">Guardar formato</button>
    </div>
  </form>
</section>

<section class="superadmin-mix-format hidden" data-mix-panel="parenteral">
  <form class="superadmin-npt-form" onsubmit="event.preventDefault()">
    <header class="superadmin-mix-format-toolbar"><div><h2>Formato de solicitud de servicio</h2><p>Solicitud de Nutricion Parenteral</p></div><button type="button" data-close-mix>&larr; Atras</button></header>
    <div class="superadmin-npt-body">
      <fieldset><legend>PACIENTE Y UBICACION</legend><div class="superadmin-npt-grid">
        <label>Paciente nombre(s)*<input required></label><label>Paciente apellidos*<input required></label><label>Servicio*<input required value="Nutricion clinica"></label>
        <label>Cama<input></label><label>Piso<input></label><label>Registro*<input required></label>
        <label class="is-span-2">Diagnostico*<input required></label><label>Peso kg*<input required type="number" min="0" step="0.01"></label>
        <label>Sexo*<select required><option value="">Seleccionar sexo</option><option>Femenino</option><option>Masculino</option></select></label><label>Fecha de nacimiento*<input required type="date"></label>
      </div></fieldset>
      <fieldset><legend>ADMINISTRACION Y MEZCLA</legend><div class="superadmin-npt-grid">
        <label>Via de administracion*<select required><option>Central</option><option>Periferica</option></select></label><label>Tiempo de infusion h*<input required type="number" min="0" step="0.5"></label><label>Velocidad ml/hr*<input required type="number" min="0" step="0.01"></label>
        <label>Sobrellenado ml<input type="number" min="0"></label><label>Volumen total ml*<input required type="number" min="0"></label><label>NPT*<select required><option>Individualizada</option><option>Estandar</option></select></label>
      </div></fieldset>
      <fieldset><legend>PRODUCTOS ACTIVOS DEL CATALOGO</legend><div class="superadmin-npt-products">
        @foreach ($nptProducts as $index => $product)<label>{{ $product }}<input type="number" min="0" step="0.01" name="npt_product_{{ $index }}"></label>@endforeach
        <label>Set de infusion<select><option>No</option><option>Si</option></select></label>
      </div></fieldset>
      <fieldset><legend>ENTREGA Y RESPONSABLE MEDICO</legend><div class="superadmin-npt-grid">
        <label class="is-span-2">Observaciones<textarea></textarea></label><label>Fecha y hora de entrega*<input required type="datetime-local"></label>
        <label>Hospital destino*<select required><option value="">Seleccionar hospital destino</option><option>Hospital General Federico Gomez</option><option>Hospital General Bajio</option><option>Clinica Queretaro Sur</option></select></label><label>Nombre del medico*<input required></label><label>Cedula profesional*<input required></label>
      </div></fieldset>
      <button class="superadmin-oncology-save" type="submit">Guardar formato</button>
    </div>
  </form>
</section>

<script>
  (() => {
    const selector = document.querySelector('[data-mix-selector]');
    const panels = [...document.querySelectorAll('[data-mix-panel]')];
    document.querySelectorAll('[data-open-mix]').forEach((button) => button.addEventListener('click', () => { selector.classList.add('hidden'); panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.mixPanel !== button.dataset.openMix)); window.scrollTo({ top: 0, behavior: 'smooth' }); }));
    document.querySelectorAll('[data-close-mix]').forEach((button) => button.addEventListener('click', () => { panels.forEach((panel) => panel.classList.add('hidden')); selector.classList.remove('hidden'); window.scrollTo({ top: 0, behavior: 'smooth' }); }));
  })();
</script>
