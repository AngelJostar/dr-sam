<?php if($requestType === 'chemo'): ?>
  <?php
    $oncologyMedicines = ['Ciclofosfamida','Ifosfamida','Cisplatino','Carboplatino','Oxaliplatino','Paclitaxel','Docetaxel','Doxorrubicina','Epirrubicina','Fluorouracilo 5-FU','Metotrexato','Gemcitabina','Citarabina','Vincristina','Vinblastina','Etoposido','Irinotecan','Rituximab','Trastuzumab','Pembrolizumab','Nivolumab','Ondansetron','Dexametasona'];
  ?>
  <fieldset class="doctor-specialized-section wide">
    <legend>Paciente y datos de la solicitud</legend>
    <div class="doctor-specialized-grid">
      <label>Fecha de solicitud<input type="date" name="oncology[request_date]" value="<?php echo e(now()->toDateString()); ?>"></label>
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
      <tbody><?php for($row = 0; $row < 8; $row++): ?><tr>
        <td><?php echo e($row + 1); ?></td>
        <td><select name="oncology[medications][<?php echo e($row); ?>][medication]"><option value="">Seleccionar</option><?php $__currentLoopData = $oncologyMedicines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $medicine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option><?php echo e($medicine); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></td>
        <td><input name="oncology[medications][<?php echo e($row); ?>][dose]"></td>
        <td class="doctor-check-cluster"><?php $__currentLoopData = ['CS','DX','Otro']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $diluent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox" name="oncology[medications][<?php echo e($row); ?>][diluents][]" value="<?php echo e($diluent); ?>"><?php echo e($diluent); ?></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></td>
        <td><input type="number" min="0" step="0.01" name="oncology[medications][<?php echo e($row); ?>][dilution_volume]"></td>
        <td><input type="number" min="0" name="oncology[medications][<?php echo e($row); ?>][boluses_per_day]"></td>
        <td><input type="number" min="0" name="oncology[medications][<?php echo e($row); ?>][infusion_minutes]"></td>
        <td class="doctor-check-cluster"><?php $__currentLoopData = ['IV','IM','SC','Otro']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox" name="oncology[medications][<?php echo e($row); ?>][routes][]" value="<?php echo e($route); ?>"><?php echo e($route); ?></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></td>
        <td><div class="doctor-date-cluster"><?php for($date = 0; $date < 6; $date++): ?><input type="date" name="oncology[medications][<?php echo e($row); ?>][delivery_dates][]"><?php endfor; ?></div></td>
      </tr><?php endfor; ?></tbody>
    </table></div>
  </fieldset>
  <fieldset class="doctor-specialized-section wide">
    <legend>Entrega y responsable médico</legend>
    <div class="doctor-specialized-grid">
      <label>Horario preferido de infusión<input type="time" name="oncology[preferred_infusion_time]"></label>
      <label>Nombre del médico<input name="oncology[doctor_name]" value="<?php echo e($doctor->full_name); ?>"></label>
      <label>Cédula profesional<input name="oncology[professional_license]" value="<?php echo e($doctor->professional_license); ?>"></label>
      <label>Carta de autorización<input type="file" name="authorization_file" accept=".pdf,.jpg,.jpeg,.png"></label>
    </div>
  </fieldset>
<?php elseif($requestType === 'npt'): ?>
  <?php
    $nptProducts = [
      'amino_acids_standard_10'=>'Aminoácidos estándar al 10% g/kg','amino_acids_pediatric_10'=>'Aminoácidos pediátricos al 10% g/kg','amino_acids_branched_8'=>'Aminoácidos al 8% de cadena ramificada g/kg','amino_acids_nephropathy_5_4'=>'Aminoácidos para nefropatías 5.4% g/kg','glucose_50'=>'Solución glucosada al 50% g/kg','lipids_20'=>'Lípidos de cadena media y larga al 20% g/kg','omega3_lipids_20'=>'Lípidos con Omega 3 al 20% g/kg','sodium_chloride_17_7'=>'Cloruro de sodio 17.7% mEq/kg','potassium_acetate_2'=>'Acetato de potasio 2 mEq/ml','sodium_acetate_4'=>'Acetato de sodio 4 mEq/ml','potassium_phosphate_2'=>'Fosfato de potasio 2 mEq/ml','magnesium_sulfate_0_81'=>'Sulfato de magnesio 0.81 mEq/ml','calcium_gluconate_0_465'=>'Gluconato de calcio 0.465 mEq/ml','potassium_chloride_4'=>'Cloruro de potasio 4 mEq/ml','fish_oil_10'=>'Emulsión de aceite de pescado al 10%','folinic_acid_12_5'=>'Ácido folínico 12.5 mg/ml','albumin_25'=>'Albúmina 25%','vitamin_c_100'=>'Vitamina C 100 mg/ml','glutamine_20'=>'Glutamina 20%','vitamin_k_10'=>'Vitamina K 10 mg/ml','chromium_4'=>'Cromo 4 mcg/ml','zinc_1'=>'Zinc 1 mg/ml','heparin_1000'=>'Heparina 1000 UI/ml','l_cysteine_50'=>'L-Cisteína 50 mg/ml','l_carnitine_200'=>'L-Carnitina 200 mg/ml','insulin_100'=>'Insulina 100 UI/ml','multivitamin'=>'Multivitamínico ml','pediatric_multivitamin'=>'Multivitamínico pediátrico ml','trace_elements'=>'Oligoelementos ml',
    ];
  ?>
  <fieldset class="doctor-specialized-section wide"><legend>Paciente y ubicación</legend><div class="doctor-specialized-grid">
    <label>Servicio clínico<input name="npt[clinical_service]" value="Nutrición clínica"></label><label>Cama<input name="npt[bed]"></label><label>Piso<input name="npt[floor]"></label>
    <label>Registro*<input name="npt[registration]" required></label><label>Peso kg*<input type="number" min="0" step="0.01" name="npt[weight]" required></label>
    <label>Sexo<select name="npt[sex]"><option value="">Seleccionar sexo</option><option>Femenino</option><option>Masculino</option><option>Otro</option></select></label><label>Fecha de nacimiento*<input type="date" name="npt[birth_date]" required></label>
  </div></fieldset>
  <fieldset class="doctor-specialized-section wide"><legend>Administración y mezcla</legend><div class="doctor-specialized-grid">
    <label>Vía de administración*<select name="npt[route]" required><option>Central</option><option>Periferica</option></select></label><label>Tiempo de infusión h*<input type="number" min="0" step="0.01" name="npt[infusion_hours]" value="24" required></label>
    <label>Velocidad ml/h<input type="number" min="0" step="0.01" name="npt[infusion_rate]"></label><label>Sobrerellenado ml<input type="number" min="0" step="0.01" name="npt[overfill]"></label>
    <label>Volumen total ml*<input type="number" min="0" step="0.01" name="npt[total_volume]" required></label><label>NPT*<select name="npt[npt_type]" required><option>Individualizada</option><option>Tricamara</option><option>Pediatrica</option></select></label>
  </div></fieldset>
  <fieldset class="doctor-specialized-section wide"><legend>Productos activos del catálogo</legend><div class="doctor-products-grid"><?php $__currentLoopData = $nptProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><?php echo e($label); ?><input type="number" min="0" step="0.01" name="npt[products][<?php echo e($key); ?>]"></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><label>Set de infusión<select name="npt[infusion_set]"><option>No</option><option>Si</option></select></label></div></fieldset>
  <fieldset class="doctor-specialized-section wide"><legend>Entrega y responsable médico</legend><div class="doctor-specialized-grid">
    <label>Fecha y hora de entrega*<input type="datetime-local" name="npt[delivery_at]" required></label><label>Hospital destino*<input name="npt[destination_hospital]" value="<?php echo e($doctor->medicalUnit?->name); ?>" required></label>
    <label>Nombre del médico*<input name="npt[doctor_name]" value="<?php echo e($doctor->full_name); ?>" required></label><label>Cédula profesional*<input name="npt[professional_license]" value="<?php echo e($doctor->professional_license); ?>" required></label>
  </div></fieldset>
<?php else: ?>
  <label>Estudio o procedimiento<input name="medication" placeholder="Ej. Biometría hemática"></label>
  <label>Indicaciones de muestra<input name="dose" placeholder="Ayuno, tipo de muestra..."></label>
<?php endif; ?>
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/doctor/partials/specialized-request-fields.blade.php ENDPATH**/ ?>