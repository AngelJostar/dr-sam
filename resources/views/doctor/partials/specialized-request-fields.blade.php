@if($requestType === 'chemo')
  @php
    $oncologyMedicines = ['Ciclofosfamida','Ifosfamida','Cisplatino','Carboplatino','Oxaliplatino','Paclitaxel','Docetaxel','Doxorrubicina','Epirrubicina','Fluorouracilo 5-FU','Metotrexato','Gemcitabina','Citarabina','Vincristina','Vinblastina','Etoposido','Irinotecan','Rituximab','Trastuzumab','Pembrolizumab','Nivolumab','Ondansetron','Dexametasona'];
  @endphp
  <fieldset class="doctor-specialized-section wide">
    <legend>Paciente y datos de la solicitud</legend>
    <div class="doctor-specialized-grid">
      <label>Fecha de solicitud<input type="date" name="oncology[request_date]" value="{{ now()->toDateString() }}"></label>
      <label>Piso<input name="oncology[floor]"></label><label>Cama<input name="oncology[bed]"></label>
      <label>Sexo<select name="oncology[sex]"><option value="">Seleccionar</option><option>Femenino</option><option>Masculino</option><option>Otro</option></select></label>
      <label>Edad<input type="number" min="0" max="130" name="oncology[age]"></label>
      <label>Peso kg<input type="number" min="0" step="0.01" name="oncology[weight]"></label>
      <label>Fecha de nacimiento<input type="date" name="oncology[birth_date]"></label>
      <label>Superficie corporal<input type="number" min="0" step="0.01" name="oncology[body_surface]"></label>
    </div>
  </fieldset>
  <fieldset class="doctor-specialized-section wide">
    <legend>Solicitud de oncológicos</legend>
    <div class="doctor-oncology-table-scroll"><table class="doctor-oncology-table">
      <thead><tr><th>No.</th><th>Medicamento*</th><th>Dosis</th><th>Diluyente</th><th>Volumen ml</th><th>Bolus/día</th><th>Infusión min</th><th>Vía</th><th>Fechas de entrega</th></tr></thead>
      <tbody>@for($row = 0; $row < 8; $row++)<tr>
        <td>{{ $row + 1 }}</td>
        <td><select name="oncology[medications][{{ $row }}][medication]"><option value="">Seleccionar</option>@foreach($oncologyMedicines as $medicine)<option>{{ $medicine }}</option>@endforeach</select></td>
        <td><input name="oncology[medications][{{ $row }}][dose]"></td>
        <td class="doctor-check-cluster">@foreach(['CS','DX','Otro'] as $diluent)<label><input type="checkbox" name="oncology[medications][{{ $row }}][diluents][]" value="{{ $diluent }}">{{ $diluent }}</label>@endforeach</td>
        <td><input type="number" min="0" step="0.01" name="oncology[medications][{{ $row }}][dilution_volume]"></td>
        <td><input type="number" min="0" name="oncology[medications][{{ $row }}][boluses_per_day]"></td>
        <td><input type="number" min="0" name="oncology[medications][{{ $row }}][infusion_minutes]"></td>
        <td class="doctor-check-cluster">@foreach(['IV','IM','SC','Otro'] as $route)<label><input type="checkbox" name="oncology[medications][{{ $row }}][routes][]" value="{{ $route }}">{{ $route }}</label>@endforeach</td>
        <td><div class="doctor-date-cluster">@for($date = 0; $date < 6; $date++)<input type="date" name="oncology[medications][{{ $row }}][delivery_dates][]">@endfor</div></td>
      </tr>@endfor</tbody>
    </table></div>
  </fieldset>
  @if($doctor->medicalUnit?->cbta_external_code)
    <fieldset class="doctor-specialized-section wide">
      <input type="hidden" name="integration_catalog_version" value="{{ $cbtaCatalogVersions['oncology'] }}">
      <legend>Catálogo operativo de Mezclas</legend>
      @if($cbtaCatalogError)
        <p class="doctor-workspace-error">{{ $cbtaCatalogError }}</p>
      @elseif($cbtaCatalogs['oncology']->isEmpty())
        <p class="doctor-workspace-error">La unidad no tiene productos oncológicos disponibles en Mezclas.</p>
      @else
        <p>Selecciona la presentación y captura la cantidad exacta que Mezclas debe prevalidar.</p>
        <div class="doctor-specialized-grid">
          @for($row = 0; $row < 8; $row++)
            <label>Producto {{ $row + 1 }}
              <select name="integration_items[{{ $row }}][catalog_item]">
                <option value="">Seleccionar</option>
                @foreach($cbtaCatalogs['oncology'] as $item)
                  <option value="{{ $item['product_code'] }}|{{ $item['presentation_code'] }}">{{ $item['generic_name'] }} — {{ $item['presentation'] }} {{ data_get($item, 'content.value') }} {{ data_get($item, 'content.unit') }}</option>
                @endforeach
              </select>
            </label>
            <label>Cantidad<input type="number" min="0.01" step="0.01" name="integration_items[{{ $row }}][quantity]"></label>
            <label>Unidad<select name="integration_items[{{ $row }}][unit]"><option value="mg">mg</option><option value="unit">Frasco / unidad</option></select></label>
          @endfor
        </div>
      @endif
      @error('integration_items')<p class="doctor-workspace-error">{{ $message }}</p>@enderror
    </fieldset>
  @endif
  <fieldset class="doctor-specialized-section wide">
    <legend>Entrega y responsable médico</legend>
    <div class="doctor-specialized-grid">
      <label>Horario preferido de infusión<input type="time" name="oncology[preferred_infusion_time]"></label>
      <label>Nombre del médico<input name="oncology[doctor_name]" value="{{ $doctor->full_name }}"></label>
      <label>Cédula profesional<input name="oncology[professional_license]" value="{{ $doctor->professional_license }}"></label>
      <label>Carta de autorización<input type="file" name="authorization_file" accept=".pdf,.jpg,.jpeg,.png"></label>
    </div>
  </fieldset>
@elseif($requestType === 'npt')
  @php
    $nptProducts = [
      'amino_acids_standard_10'=>'Aminoácidos estándar al 10% g/kg','amino_acids_pediatric_10'=>'Aminoácidos pediátricos al 10% g/kg','amino_acids_branched_8'=>'Aminoácidos al 8% de cadena ramificada g/kg','amino_acids_nephropathy_5_4'=>'Aminoácidos para nefropatías 5.4% g/kg','glucose_50'=>'Solución glucosada al 50% g/kg','lipids_20'=>'Lípidos de cadena media y larga al 20% g/kg','omega3_lipids_20'=>'Lípidos con Omega 3 al 20% g/kg','sodium_chloride_17_7'=>'Cloruro de sodio 17.7% mEq/kg','potassium_acetate_2'=>'Acetato de potasio 2 mEq/ml','sodium_acetate_4'=>'Acetato de sodio 4 mEq/ml','potassium_phosphate_2'=>'Fosfato de potasio 2 mEq/ml','magnesium_sulfate_0_81'=>'Sulfato de magnesio 0.81 mEq/ml','calcium_gluconate_0_465'=>'Gluconato de calcio 0.465 mEq/ml','potassium_chloride_4'=>'Cloruro de potasio 4 mEq/ml','fish_oil_10'=>'Emulsión de aceite de pescado al 10%','folinic_acid_12_5'=>'Ácido folínico 12.5 mg/ml','albumin_25'=>'Albúmina 25%','vitamin_c_100'=>'Vitamina C 100 mg/ml','glutamine_20'=>'Glutamina 20%','vitamin_k_10'=>'Vitamina K 10 mg/ml','chromium_4'=>'Cromo 4 mcg/ml','zinc_1'=>'Zinc 1 mg/ml','heparin_1000'=>'Heparina 1000 UI/ml','l_cysteine_50'=>'L-Cisteína 50 mg/ml','l_carnitine_200'=>'L-Carnitina 200 mg/ml','insulin_100'=>'Insulina 100 UI/ml','multivitamin'=>'Multivitamínico ml','pediatric_multivitamin'=>'Multivitamínico pediátrico ml','trace_elements'=>'Oligoelementos ml',
    ];
    $classifyNptItem = static function (array $item): ?string {
      $category = str(data_get($item, 'category', ''))->lower()->ascii()->toString();
      $text = str(data_get($item, 'generic_name', '').' '.data_get($item, 'commercial_name', '').' '.data_get($item, 'product_code', ''))->lower()->ascii()->toString();

      return match (true) {
        str_contains($category, 'bolsa'), str_contains($category, 'set de infusion'), str_contains($category, 'servicio'), $category === 'otra' => null,
        str_contains($category, 'aditivo') => 'additives',
        str_contains($category, 'amino') => 'amino_acids',
        str_contains($category, 'carbo'), str_contains($category, 'glucos'), str_contains($category, 'dextros') => 'carbohydrates',
        str_contains($category, 'lipid') => 'lipids',
        str_contains($category, 'electrolito'), str_contains($category, 'cloruro de sodio') => 'electrolytes',
        str_contains($text, 'amino') => 'amino_acids',
        str_contains($text, 'glucos'), str_contains($text, 'dextros'), str_contains($text, 'carbo') => 'carbohydrates',
        str_contains($text, 'lipid'), str_contains($text, 'omega'), str_contains($text, 'aceite de pescado') => 'lipids',
        str_contains($text, 'sodio'), str_contains($text, 'potasio'), str_contains($text, 'fosfato'), str_contains($text, 'magnesio'), str_contains($text, 'calcio'), str_contains($text, 'cloruro'), str_contains($text, 'acetato') => 'electrolytes',
        default => null,
      };
    };
    $externalNptGroups = collect($cbtaCatalogs['npt'] ?? [])->groupBy(fn (array $item) => $classifyNptItem($item));
    // Los aditivos catalogados como medicamentos sí forman parte de la captura.
    // Los materiales auxiliares se determinan durante la materialización.
    $nptGroupLabels = ['amino_acids' => 'Aminoácidos', 'carbohydrates' => 'Carbohidratos', 'lipids' => 'Lípidos', 'electrolytes' => 'Electrolitos', 'additives' => 'Aditivos'];
  @endphp
  <fieldset class="doctor-specialized-section doctor-npt-form-section wide"><legend>Datos del paciente y servicio</legend><div class="doctor-specialized-grid">
    <label class="doctor-npt-patient-field">Paciente*<select name="patient_id" required><option value="">Seleccionar paciente</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected((string) old('patient_id') === (string) $patient->id)>{{ $patient->full_name }} - {{ $patient->platform_number }}</option>@endforeach</select></label>
    <label>Servicio*<input name="service" value="{{ old('service', 'Nutrición parenteral') }}" maxlength="100" required></label><label>Cama<input name="npt[bed]" value="{{ old('npt.bed') }}" maxlength="50"></label><label>Piso<input name="npt[floor]" value="{{ old('npt.floor') }}" maxlength="50"></label>
    <label>Registro<input name="npt[registration]" value="{{ old('npt.registration') }}" maxlength="50"></label><label class="doctor-npt-diagnosis-field">Diagnóstico<textarea name="diagnosis" rows="2" maxlength="255">{{ old('diagnosis') }}</textarea></label>
    <label>Peso (kg)*<input type="number" min="0" step="0.001" name="npt[weight]" value="{{ old('npt.weight') }}" required></label>
    <label>Sexo<select name="npt[sex]"><option value="">Seleccionar sexo</option>@foreach(['Femenino','Masculino'] as $sex)<option @selected(old('npt.sex') === $sex)>{{ $sex }}</option>@endforeach</select></label><label>Fecha de nacimiento*<input type="date" name="npt[birth_date]" value="{{ old('npt.birth_date') }}" max="{{ now()->toDateString() }}" required></label>
  </div></fieldset>
  <fieldset class="doctor-specialized-section doctor-npt-form-section wide"><legend>Administración de la mezcla</legend><div class="doctor-specialized-grid">
    <label>Vía de administración*<select name="npt[route]" required><option value="Central" @selected(old('npt.route', 'Central') === 'Central')>Central</option><option value="Periferica" @selected(old('npt.route') === 'Periferica')>Periférica</option></select></label><label>Tiempo de infusión (h)<input type="number" min="0" step="0.01" name="npt[infusion_hours]" value="{{ old('npt.infusion_hours', 24) }}" data-npt-infusion-time></label>
    <label>Velocidad de infusión (ml/h)<input type="number" min="0" step="0.001" name="npt[infusion_rate]" value="{{ old('npt.infusion_rate') }}" data-npt-infusion-rate></label><label>Sobrellenado (ml)<input type="number" min="0" step="0.0001" name="npt[overfill]" value="{{ old('npt.overfill') }}"></label>
    <label>Volumen total (ml)<input type="number" min="0" step="0.0001" name="npt[total_volume]" value="{{ old('npt.total_volume') }}"></label><label>NPT*<select name="npt[npt_type]" required>@foreach(['Individualizada'=>'ADULTO','Pediatrica'=>'PEDIÁTRICO'] as $value=>$label)<option value="{{ $value }}" @selected(old('npt.npt_type', 'Individualizada') === $value)>{{ $label }}</option>@endforeach</select></label>
  </div></fieldset>
  <fieldset class="doctor-specialized-section doctor-npt-catalog-section wide"><legend>Componentes de la nutrición parenteral</legend>
    @if($doctor->medicalUnit?->cbta_external_code)
      <input type="hidden" name="integration_catalog_version" value="{{ $cbtaCatalogVersions['npt'] }}">
      @if($cbtaCatalogError)
        <p class="doctor-workspace-error">{{ $cbtaCatalogError }}</p>
      @elseif($cbtaCatalogs['npt']->isEmpty())
        <p class="doctor-workspace-error">La unidad no tiene productos NPT disponibles en Mezclas.</p>
      @else
        <p class="doctor-npt-catalog-help">Las presentaciones y su disponibilidad provienen del catálogo habilitado en CBTA para esta unidad.</p>
        <div class="doctor-npt-groups">
          @foreach($nptGroupLabels as $groupKey => $groupLabel)
            @php($groupItems = $externalNptGroups->get($groupKey, collect()))
            @continue($groupItems->isEmpty())
            <section class="doctor-npt-product-group"><h3>{{ $groupLabel }}</h3><div class="doctor-products-grid">
              @foreach($groupItems as $item)
                @php($row = collect($cbtaCatalogs['npt'])->search(fn ($catalogItem) => data_get($catalogItem, 'product_code') === data_get($item, 'product_code') && data_get($catalogItem, 'presentation_code') === data_get($item, 'presentation_code')))
                <label><span>{{ $item['generic_name'] }}</span><small>{{ $item['commercial_name'] ?: $item['presentation'] }}</small>
                  <input type="hidden" name="integration_items[{{ $row }}][catalog_item]" value="{{ $item['product_code'] }}|{{ $item['presentation_code'] }}"><input type="hidden" name="integration_items[{{ $row }}][unit]" value="ml">
                  <span class="doctor-npt-quantity"><input type="number" min="0.01" step="0.01" name="integration_items[{{ $row }}][quantity]" value="{{ old('integration_items.'.$row.'.quantity') }}" placeholder="0.00"><b>ml</b></span>
                </label>
              @endforeach
            </div></section>
          @endforeach
        </div>
      @endif
      @error('integration_items')<p class="doctor-workspace-error">{{ $message }}</p>@enderror
    @else
      <div class="doctor-products-grid">@foreach(collect($nptProducts)->only(['amino_acids_standard_10','amino_acids_pediatric_10','amino_acids_branched_8','amino_acids_nephropathy_5_4','glucose_50','lipids_20','omega3_lipids_20','sodium_chloride_17_7','potassium_acetate_2','sodium_acetate_4','potassium_phosphate_2','magnesium_sulfate_0_81','calcium_gluconate_0_465','potassium_chloride_4']) as $key=>$label)<label>{{ $label }}<input type="number" min="0" step="0.01" name="npt[products][{{ $key }}]"></label>@endforeach</div>
    @endif
  </fieldset>
  <fieldset class="doctor-specialized-section doctor-npt-form-section wide"><legend>Entrega y responsable médico</legend><div class="doctor-specialized-grid">
    <input type="hidden" name="npt[destination_hospital]" value="{{ old('npt.destination_hospital', $doctor->medicalUnit?->name) }}">
    <label>Fecha y hora de entrega*<input type="datetime-local" name="npt[delivery_at]" value="{{ old('npt.delivery_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required></label>
    <label>Nombre del médico*<input name="npt[doctor_name]" value="{{ old('npt.doctor_name', $doctor->full_name) }}" maxlength="255" required></label><label>Cédula profesional*<input name="npt[professional_license]" value="{{ old('npt.professional_license', $doctor->professional_license) }}" maxlength="50" required></label>
  </div></fieldset>
@else
  <label>Estudio o procedimiento<input name="medication" placeholder="Ej. Biometría hemática"></label>
  <label>Indicaciones de muestra<input name="dose" placeholder="Ayuno, tipo de muestra..."></label>
@endif
