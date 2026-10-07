<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#082441">
  <meta name="description" content="Se viene Klini. Movimiento, hábitos, comunidad y una plataforma digital para acompañar tu bienestar. Descubre lo que estamos creando.">
  <meta property="og:title" content="Klini — Muévete. Conecta. Sé parte.">
  <meta property="og:description" content="Muévete. Conecta. Sé parte. Conoce la próxima comunidad de bienestar de Klini.">
  <meta property="og:type" content="website">
  <title>Klini — Tu próxima comunidad de bienestar</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('brand/klini/favicon.svg') }}">
  <link rel="preload" href="{{ asset('brand/klini/dejavu-bold.woff') }}" as="font" type="font/woff" crossorigin>
  <link rel="stylesheet" href="{{ asset('css/klini-landing.css') }}?v={{ filemtime(public_path('css/klini-landing.css')) }}">
  <script src="{{ asset('js/klini-landing.js') }}?v={{ filemtime(public_path('js/klini-landing.js')) }}" defer></script>
</head>
<body data-access-url="{{ route('login') }}">
  <a class="skip" href="#contenido">Saltar al contenido</a>
  <svg class="icon-defs" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><defs>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></symbol>
    <symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 9h18"/></symbol>
    <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol>
    <symbol id="i-heart" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/><path d="m4 12 4-.1 2-4 3 8 2-4h5"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></symbol>
    <symbol id="i-play" viewBox="0 0 24 24"><path d="m9 5 11 7-11 7Z"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-lock" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="12" rx="3"/><path d="M8 10V6a4 4 0 0 1 8 0v4M12 15v2"/></symbol>

    <symbol id="i-activity" viewBox="0 0 24 24"><path d="M2 12h5l3-9 4 18 3-9h5"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/><circle cx="9" cy="7" r="4"/></symbol>
    <symbol id="i-watch" viewBox="0 0 24 24"><rect x="5" y="5" width="14" height="14" rx="4"/><path d="M8 5V1h8v4M8 19v4h8v-4m-4-11 0 4 3 1"/></symbol>
    <symbol id="i-drop" viewBox="0 0 24 24"><path d="M12 2S5 10 5 15a7 7 0 0 0 14 0C19 10 12 2 12 2Z"/></symbol>
    <symbol id="i-moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 0 1 11.2 3 9 9 0 1 0 21 12.8Z"/></symbol>
    <symbol id="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></symbol>
  </defs></svg>

  <header class="header">
    <div class="nav-shell">
      <a href="#inicio" class="brand brand-wellness" aria-label="Klini, inicio"><img src="{{ asset('brand/klini/klini.svg') }}" alt="Klini" width="120" height="56"></a>
      <nav class="nav-links" aria-label="Navegación principal" id="main-nav">
        <a href="#comunidad">La comunidad</a><a href="#experiencia">Tu bienestar digital</a><a href="#como-funciona">Lo que viene</a>
      </nav>
      <div class="nav-actions">
        <button class="mobile-menu icon-button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav"><svg class="icon"><use href="#i-menu"/></svg></button>
        <button class="login-trigger" data-login aria-label="Ingresar a Klini"><span>Ingresar</span><span class="user-circle"><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg></span></button>
        <button class="header-signup" type="button" data-signup><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg><span>Únete, crea tu cuenta</span></button>
      </div>
    </div>
  </header>

  <main id="contenido">
    <section class="hero section-shell wellness-hero" id="inicio" aria-labelledby="hero-title">
      <div class="hero-copy">
        <p class="eyebrow">KLINI</p>
        <h1 id="hero-title">Muévete.<br>Conecta.<br><span>Sé parte.</span></h1>
        <p class="hero-description">Se viene una nueva forma de vivir tu bienestar. Movimiento, hábitos y personas que te inspiran, conectados en una misma comunidad.</p>
        <div class="hero-actions"><button class="button button-dark hero-signup" type="button" data-signup>Únete, crea tu cuenta <span aria-hidden="true">→</span></button><a class="text-link wellness-explore" href="#comunidad">Descubre lo que viene</a></div>
        <p class="hero-footnote">Tu ritmo. Tu gente. Un nuevo comienzo.</p>
      </div>
      <div class="hero-art">
        <div class="photo-frame"><img src="{{ asset('brand/klini/wellness-yoga.webp') }}" alt="Jóvenes comparten una sesión de yoga y estiramiento al aire libre, con luz de la mañana." width="1120" height="1400" fetchpriority="high"></div>
        <div class="vertical-caption">BIENESTAR QUE SE COMPARTE.</div>
        <div class="floating-note note-top"><span class="note-icon"><svg class="icon"><use href="#i-sun"/></svg></span><span>Encuentra tu ritmo.<br><strong>Disfruta el momento.</strong></span></div>
        <div class="floating-note note-bottom"><span class="document-symbol"><svg class="icon"><use href="#i-users"/></svg></span><div><span class="small-label">ALGO BUENO ESTÁ POR EMPEZAR</span><strong>Tu próxima comunidad.</strong><span class="note-bottom-text">El bienestar también se vive en compañía.</span></div></div>
        <div class="art-caption"><span>MÁS CONEXIÓN.</span><strong>Más ganas de estar bien.</strong></div>
      </div>
    </section>
    <div class="benefit-strip section-shell" aria-label="La esencia de Klini">
      <div><span>01</span><svg class="icon"><use href="#i-activity"/></svg><strong>Movimiento a tu ritmo</strong></div>
      <div><span>02</span><svg class="icon"><use href="#i-users"/></svg><strong>Una comunidad que inspira</strong></div>
      <div><span>03</span><svg class="icon"><use href="#i-watch"/></svg><strong>Bienestar en digital</strong></div>
    </div>

    <section class="community-section section-shell" id="comunidad" aria-labelledby="community-title">
      <div class="section-heading"><div><p class="eyebrow">ESTO APENAS COMIENZA</p><h2 id="community-title">Más momentos<br><span>que te hacen bien.</span></h2></div><p>Estamos creando un punto de encuentro para movernos, compartir y darle más espacio al bienestar de todos los días.</p></div>
      <div class="wellness-stories">
        <article class="wellness-story"><div class="story-image"><img src="{{ asset('brand/klini/wellness-running.webp') }}" alt="Tres jóvenes corren juntos en un parque y disfrutan de hacer ejercicio en compañía." width="1200" height="900" loading="lazy"><span class="photo-tag">MOVIMIENTO + COMUNIDAD</span></div><div class="story-copy"><span class="story-number">01 / ENCUENTRA TU GENTE</span><h3>Un plan que sí te mueve.</h3><p>Yoga, ejercicio y encuentros con personas que comparten tus ganas de sentirte bien. A tu nivel. A tu manera.</p></div></article>
        <article class="wellness-story"><div class="story-image"><img src="{{ asset('brand/klini/wellness-watch.webp') }}" alt="Una persona consulta la señal de frecuencia cardíaca en su reloj inteligente después de hacer ejercicio." width="1200" height="900" loading="lazy"><span class="photo-tag">TU CUERPO + TUS DATOS</span></div><div class="story-copy"><span class="story-number">02 / CONECTA CONTIGO</span><h3>Tu ritmo también cuenta.</h3><p>Actividad, descanso y señales de tu cuerpo. Imaginamos una plataforma para dar contexto a tus registros y acompañar tus hábitos.</p></div></article>
      </div>
    </section>

    <section class="manifesto wellness-manifesto" id="esencia" aria-labelledby="manifesto-title">
      <div class="section-shell manifesto-grid"><div><p class="eyebrow mint-text">EL BIENESTAR ES PERSONAL. LA ENERGÍA SE COMPARTE.</p><h2 id="manifesto-title">Tú pones el ritmo.<br>La comunidad,<br><span>la energía.</span></h2></div><div class="manifesto-copy"><p>Una clase que te inspira.<br>Una caminata que se vuelve plan.<br>Un hábito que haces tuyo.</p><p>Klini nace para conectar esos momentos en una comunidad y una experiencia digital. Un lugar para descubrir, registrar y compartir tu camino hacia el bienestar.</p><button class="underline-link manifesto-join" type="button" data-signup>Quiero vivirlo con Klini</button></div></div>
      <div class="manifesto-bottom section-shell"><span>MOVIMIENTO</span><span class="separator-dot"></span><span>HÁBITOS</span><span class="separator-dot"></span><span>COMUNIDAD</span></div>
    </section>

    <section class="experience section-shell" id="experiencia" aria-labelledby="experience-title">
      <div class="section-heading"><div><p class="eyebrow">UNA MIRADA A LO QUE ESTAMOS CREANDO</p><h2 id="experience-title">Vive tu bienestar.<br><span>Dale un espacio digital.</span></h2></div><p>Así imaginamos tu día con Klini: tus hábitos, las señales de tu cuerpo y tu comunidad, en una misma plataforma.</p></div>
      <div class="experience-grid">
        <div class="feature-tabs" role="tablist" aria-label="Explorar Klini" aria-orientation="vertical">
          <button class="feature-tab active" id="tab-habitos" role="tab" aria-selected="true" aria-controls="panel-habitos" data-feature="habitos"><span class="feature-icon"><svg class="icon"><use href="#i-sun"/></svg></span><span><strong>Mi día, mis hábitos</strong><span>Actividad, hidratación y descanso. Pequeños momentos que cuentan.</span></span><span class="tab-indicator">01</span></button>
          <button class="feature-tab" id="tab-senales" role="tab" aria-selected="false" aria-controls="panel-senales" tabindex="-1" data-feature="senales"><span class="feature-icon"><svg class="icon"><use href="#i-watch"/></svg></span><span><strong>Las señales de mi cuerpo</strong><span>Un lugar para observar tus datos de salud y bienestar.</span></span><span class="tab-indicator">02</span></button>
          <button class="feature-tab" id="tab-comunidad" role="tab" aria-selected="false" aria-controls="panel-comunidad" tabindex="-1" data-feature="comunidad"><span class="feature-icon"><svg class="icon"><use href="#i-users"/></svg></span><span><strong>Mi próxima comunidad</strong><span>Clases, actividades y personas para compartir el camino.</span></span><span class="tab-indicator">03</span></button>
          <p class="explorer-hint">Explora las secciones y prueba un registro de ejemplo.</p>
        </div>
        <div class="product-stage wellness-stage"><div class="product-window">
          <div class="window-top"><img src="{{ asset('brand/klini/klini.svg') }}" alt="Klini" width="120" height="36"><span>Mi bienestar</span><span class="avatar" aria-label="Perfil de ejemplo">A</span></div>
          <div class="product-panel" role="tabpanel" id="panel-habitos" aria-labelledby="tab-habitos" tabindex="0">
            <p class="small-label">TU DÍA TAMBIÉN CUENTA</p><h3>Hola, Alex.</h3><p class="product-subtitle">Un momento para conectar contigo.</p>
            <div class="habit-grid"><div class="habit-stat"><svg class="icon"><use href="#i-activity"/></svg><span>Movimiento</span><strong><span id="metric-activity">35</span> <small>min</small></strong></div><div class="habit-stat"><svg class="icon"><use href="#i-drop"/></svg><span>Hidratación</span><strong><span id="metric-water">1.5</span> <small>L</small></strong></div><div class="habit-stat"><svg class="icon"><use href="#i-moon"/></svg><span>Descanso</span><strong><span id="metric-sleep">7.5</span> <small>h</small></strong></div></div>
            <div class="daily-moment"><span class="moment-icon"><svg class="icon"><use href="#i-sun"/></svg></span><div><strong>Un espacio para tu día</strong><span>¿Qué momento quieres registrar?</span></div></div>
            <button class="demo-record-button" id="demo-record-toggle" type="button" aria-expanded="false" aria-controls="demo-record-fields"><svg class="icon"><use href="#i-plus"/></svg> Probar un registro</button>
            <div class="demo-entry" id="demo-record-fields" hidden><p class="demo-entry-note">Prueba con datos de ejemplo. No se guardan ni se envían.</p><div class="demo-entry-grid"><div><label for="demo-habit">Hábito</label><select id="demo-habit"><option value="activity">Actividad</option><option value="water">Hidratación</option><option value="sleep">Descanso</option></select></div><div><label for="demo-value" id="demo-value-label">Minutos</label><input id="demo-value" type="number" min="0" max="1440" step="1" value="35" required inputmode="decimal"></div></div><button class="button button-dark demo-save" id="demo-save" type="button">Actualizar ejemplo</button></div>
            <p class="demo-status" id="demo-status" role="status" hidden></p>
          </div>
          <div class="product-panel" role="tabpanel" id="panel-senales" aria-labelledby="tab-senales" tabindex="0" hidden>
            <p class="small-label">MÁS CONTEXTO PARA TU BIENESTAR</p><h3>Conecta con tu ritmo.</h3><p class="product-subtitle">Una propuesta para reunir tus registros.</p>
            <div class="pulse-card"><span class="pulse-label"><svg class="icon"><use href="#i-heart"/></svg> Frecuencia cardíaca</span><div><strong>72</strong><span>lpm</span><svg class="pulse-motif icon" aria-hidden="true"><use href="#i-activity"/></svg></div><span class="pulse-caption">Ejemplo de un registro · Sin lectura en vivo</span></div>
            <div class="signal-row"><span>Actividad del día</span><strong>6,420 <small>pasos</small></strong></div><div class="signal-row"><span>Descanso registrado</span><strong>7.5 <small>horas</small></strong></div>
            <p class="device-note"><svg class="icon"><use href="#i-watch"/></svg><span>La conexión con relojes y su compatibilidad se definirán para el lanzamiento.</span></p>
          </div>
          <div class="product-panel" role="tabpanel" id="panel-comunidad" aria-labelledby="tab-comunidad" tabindex="0" hidden>
            <p class="small-label">MÁS GANAS DE COMPARTIR</p><h3>Encuentra tu próximo plan.</h3><p class="product-subtitle">Una comunidad que empieza a tomar forma.</p>
            <div class="community-plan"><span class="plan-symbol"><svg class="icon"><use href="#i-sun"/></svg></span><div><strong>Yoga para empezar el día</strong><span>Respira, muévete, conecta.</span></div></div>
            <div class="community-plan"><span class="plan-symbol"><svg class="icon"><use href="#i-activity"/></svg></span><div><strong>Nos vemos en movimiento</strong><span>Una caminata. Un nuevo encuentro.</span></div></div>
            <div class="community-plan"><span class="plan-symbol"><svg class="icon"><use href="#i-users"/></svg></span><div><strong>Pequeños hábitos, juntos</strong><span>Motivación para el día a día.</span></div></div>
            <p class="device-note"><span>Conceptos de actividades para la comunidad.</span></p>
          </div>
        </div><p class="demo-caption">Adelanto conceptual · Datos de ejemplo · Proyecto en desarrollo</p></div>
      </div>
    </section>

    <section class="how-section" id="como-funciona" aria-labelledby="how-title"><div class="section-shell"><div class="section-heading"><div><p class="eyebrow">EL COMIENZO DE ALGO BUENO</p><h2 id="how-title">Más que una rutina.<br>Una forma de conectar.</h2></div><p>Esto es lo que queremos construir contigo. Una experiencia para vivir el bienestar dentro y fuera de la pantalla.</p></div><div class="steps"><article><span class="step-number">01</span><h3>Encuentra lo que te mueve.</h3><p>Explora propuestas de yoga, ejercicio y bienestar que vayan contigo.</p></article><article><span class="step-number">02</span><h3>Registra tu día.</h3><p>Dale un lugar digital a tu actividad, descanso, hidratación y datos de bienestar.</p></article><article><span class="step-number">03</span><h3>Conecta con tu gente.</h3><p>Comparte experiencias y descubre la motivación de sentirte parte de una comunidad.</p></article></div></div></section>

    <section class="faq section-shell" aria-labelledby="faq-title"><div><p class="eyebrow">LO QUE QUIERES SABER</p><h2 id="faq-title">Tu próximo<br>comienzo.</h2><p>Conoce lo que viene para Klini.</p></div><div class="faq-list">
      <details open><summary>¿Qué será Klini?<svg class="icon"><use href="#i-plus"/></svg></summary><p>Una comunidad de bienestar con una plataforma digital para acompañar tus hábitos y reunir tus registros. La propuesta conecta movimiento, experiencias compartidas y datos de tu día a día.</p></details>
      <details><summary>¿Tengo que ser deportista para unirme?<svg class="icon"><use href="#i-plus"/></svg></summary><p>No. Klini está pensado para personas que quieren darle más espacio al bienestar, tanto si están empezando como si ya tienen una rutina. Cada quien a su ritmo.</p></details>
      <details><summary>¿Ya está disponible la plataforma?<svg class="icon"><use href="#i-plus"/></svg></summary><p>Puedes ingresar a los módulos disponibles con tu usuario autorizado. La experiencia de bienestar que se muestra aquí es una vista previa con datos de ejemplo.</p></details>
      <details><summary>¿Podré conectar mi reloj inteligente?<svg class="icon"><use href="#i-plus"/></svg></summary><p>La propuesta contempla reunir registros de actividad y señales de bienestar. Las integraciones, los modelos compatibles y los datos que podrán sincronizarse se definirán para el lanzamiento. Esta página no se conecta a dispositivos.</p></details>
    </div></section>

    <section class="klini-editorial section-shell" aria-labelledby="editorial-title">
      <img src="{{ asset('brand/klini/brand-still-life.webp') }}" alt="Libreta, botella turquesa y bolígrafo sobre una superficie menta." width="1920" height="1088" loading="lazy">
      <div><p class="eyebrow">TU SALUD, EN UN SOLO LUGAR.</p><h2 id="editorial-title">Tu historia merece<br>su propio espacio.</h2><p>Conoce Klini y entra a los módulos disponibles para organizar tu información de salud.</p><a class="button button-dark" href="{{ route('login') }}">Entrar a Klini</a></div>
    </section>
    <section class="closing section-shell" aria-labelledby="closing-title"><div class="closing-inner"><p class="eyebrow">KLINI</p><h2 id="closing-title">Algo bueno<br>está por <span>venir.</span></h2><div class="closing-bottom"><p>Una nueva comunidad está por comenzar.<br>Y queremos que seas parte.</p><button class="button button-dark" type="button" data-signup><svg class="icon"><use href="#i-users"/></svg> Únete, crea tu cuenta</button></div></div></section>
  </main>
  <footer class="footer section-shell"><a href="#inicio" aria-label="Klini, volver al inicio"><img src="{{ asset('brand/klini/klini.svg') }}" alt="Klini" width="120" height="50"></a><p>Klini. Muévete. Conecta. Sé parte.</p><span>© 2026 Klini</span></footer>

  <dialog class="login-dialog" id="login-dialog" aria-labelledby="login-title" aria-describedby="login-intro">
    <button class="dialog-close icon-button" type="button" aria-label="Cerrar ingreso"><svg class="icon"><use href="#i-close"/></svg></button>
    <div class="login-brand"><img src="{{ asset('brand/klini/klini.svg') }}" alt="Klini" width="120" height="56"></div>
    <div class="login-emblem"><svg class="icon"><use href="#i-user"/></svg></div>
    <h2 id="login-title">Qué gusto verte.</h2>
    <p id="login-intro">Ingresa a tu espacio de bienestar.</p>
    <form method="POST" action="{{ route('demo-login.store') }}">
      @csrf
      @if($errors->any())<p class="login-feedback" role="alert">{{ $errors->first() }}</p>@endif
      <label for="klini-username">Usuario o correo electrónico</label>
      <input id="klini-username" name="username" autocomplete="username" placeholder="Tu usuario" value="{{ old('username') }}" required>
      <label for="klini-password">Contraseña</label>
      <div class="password-wrap"><input id="klini-password" name="password" type="password" autocomplete="current-password" placeholder="Tu contraseña" @required(!config('drsam.review_passwordless'))><button class="icon-button" type="button" id="toggle-login-password" aria-label="Mostrar contraseña" aria-pressed="false"><svg class="icon"><use href="#i-eye"/></svg></button></div>
      <button type="button" class="forgot-link" id="forgot-login-password">¿Olvidaste tu contraseña?</button>
      <p class="login-feedback" id="password-help" hidden role="status">Contacta al administrador de tu institución para recuperar el acceso a tu cuenta.</p>
      <button type="submit" class="button button-dark login-submit">Ingresar</button>
    </form>
    <p class="auth-switch"><span>¿No tienes cuenta?</span> <button type="button" data-signup>Únete, crea tu cuenta</button></p>
    <p class="login-preview">Acceso a los módulos autorizados de Klini.</p>
  </dialog>
  <dialog class="login-dialog" id="signup-dialog" aria-labelledby="signup-title" aria-describedby="signup-intro">
    <button class="dialog-close icon-button" type="button" aria-label="Cerrar"><svg class="icon"><use href="#i-close"/></svg></button>
    <div class="login-brand"><img src="{{ asset('brand/klini/klini.svg') }}" alt="Klini" width="120" height="56"></div>
    <div class="login-emblem"><svg class="icon"><use href="#i-users"/></svg></div>
    <h2 id="signup-title">Sé parte de Klini.</h2>
    <p id="signup-intro">Estamos preparando el registro para nuevos usuarios. Si ya tienes una cuenta, puedes ingresar a tu módulo.</p>
    <a class="button button-dark login-submit" href="{{ route('login') }}">Ingresar a mi cuenta</a>
  </dialog>
</body>
</html>
