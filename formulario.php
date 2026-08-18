<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https://cdn.portalfruticola.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Postula a DDC | David Del Curto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ddc-primary:#003DA6;
            --ddc-secondary:#3CB4E5;
            --ddc-text:#606060;
            --ddc-accent:#00C7B1;
            --ddc-deep:#2C2E65;
            --ddc-bright:#0662FF;
            --ddc-soft:#BFD6F7;
            --form-bg:#E9E9E9;
            --input-border:#D9DEE5;
            --white:#FFFFFF;
            --danger:#B42318;
            --success:#067647;
            --focus:#00C7B1;
        }

        * { box-sizing:border-box; }
        html { background:#fff; }
        body {
            margin:0;
            color:var(--ddc-text);
            background:#fff;
            font-family:"Titillium Web",Arial,sans-serif;
            font-size:16px;
            line-height:1.45;
        }

        .photo-hero {
            width:100%;
            height:clamp(280px,40vw,505px);
            background-image:url('https://cdn.portalfruticola.com/2023/11/PORTADA-DDC.png');
            background-position:center 48%;
            background-size:cover;
            background-repeat:no-repeat;
        }

        .application-shell {
            width:min(1065px, calc(100% - 40px));
            margin:-68px auto 70px;
            position:relative;
            z-index:2;
        }

        .intro-banner {
            position:relative;
            min-height:145px;
            padding:30px 28px 26px;
            overflow:hidden;
            color:#fff;
            background:linear-gradient(105deg, #0662FF 0%, #003DA6 72%, #003DA6 100%);
            border-bottom:5px solid #fff;
        }
        .intro-banner::before,
        .intro-banner::after {
            content:"";
            position:absolute;
            right:-40px;
            bottom:-100px;
            width:250px;
            height:250px;
            border:1px solid rgba(255,255,255,.35);
            border-radius:50%;
            transform:rotate(-18deg);
        }
        .intro-banner::after {
            right:-15px;
            bottom:-125px;
            width:300px;
            height:300px;
            border-color:rgba(255,255,255,.22);
        }
        .intro-banner h1 {
            position:relative;
            z-index:1;
            margin:0;
            color:#fff;
            font-size:clamp(2rem,4vw,2.75rem);
            font-weight:500;
            line-height:1.08;
            letter-spacing:.01em;
        }
        .intro-banner p {
            position:relative;
            z-index:1;
            margin:18px 0 0;
            color:#fff;
            font-size:.95rem;
        }
        .intro-banner p::before {
            content:"↗";
            display:inline-block;
            margin-right:4px;
            color:#fff;
        }

        .form-panel {
            background:var(--form-bg);
            padding:48px 26px 42px;
        }

        .form-inner {
            width:100%;
            margin:0 auto;
        }

        form { margin:0; }
        fieldset { border:0; margin:0 0 28px; padding:0; min-width:0; }
        fieldset:last-of-type { margin-bottom:22px; }
        legend {
            display:block;
            width:100%;
            margin:0 0 24px;
            padding:0;
            color:var(--ddc-primary);
            border:0;
            font-size:1.2rem;
            line-height:1.2;
            font-weight:700;
        }

        .grid {
            display:grid;
            grid-template-columns:repeat(12,minmax(0,1fr));
            column-gap:16px;
            row-gap:18px;
        }
        .field { grid-column:span 6; min-width:0; }
        .field.third { grid-column:span 4; }
        .field.full { grid-column:1/-1; }

        label {
            display:block;
            margin:0 0 5px;
            color:#555;
            font-size:.88rem;
            line-height:1.25;
            font-weight:500;
        }
        .optional { color:#777; font-size:.82rem; font-weight:400; }

        input, select, textarea {
            width:100%;
            min-height:40px;
            margin:0;
            padding:8px 11px;
            color:#4f4f4f;
            background:#fff;
            border:1px solid var(--input-border);
            border-radius:2px;
            box-shadow:none;
            font:inherit;
            font-size:.9rem;
            transition:border-color .15s ease, box-shadow .15s ease;
        }
        select {
            appearance:auto;
            cursor:pointer;
        }
        textarea {
            min-height:92px;
            resize:vertical;
        }
        input[type="file"] {
            min-height:42px;
            padding:7px;
        }
        input[type="checkbox"] {
            width:16px;
            height:16px;
            min-height:0;
            padding:0;
            accent-color:var(--ddc-primary);
        }
        input:focus, select:focus, textarea:focus, button:focus-visible {
            outline:2px solid var(--focus);
            outline-offset:1px;
            border-color:var(--ddc-primary);
        }
        [aria-invalid="true"] { border-color:var(--danger); }

        .foreign-fields { margin-top:18px; }
        .foreign-fields[hidden] { display:none!important; }
        .help {
            margin:5px 0 0;
            color:#777;
            font-size:.78rem;
            line-height:1.35;
        }
        .section-help { margin:-12px 0 18px; }
        .required-note {
            margin:0 0 30px;
            color:#707070;
            font-size:.82rem;
        }

        .consent {
            display:flex;
            gap:10px;
            align-items:flex-start;
            max-width:100%;
            padding:13px 14px;
            background:rgba(255,255,255,.55);
            border:1px solid #D4D8DE;
        }
        .consent input { flex:0 0 auto; margin-top:3px; }
        .consent label { margin:0; font-size:.85rem; line-height:1.4; }

        .actions {
            display:flex;
            align-items:center;
            gap:14px;
            flex-wrap:wrap;
        }
        button {
            min-height:42px;
            padding:10px 19px;
            color:#fff;
            background:#0072BC;
            border:0;
            border-radius:2px;
            font:inherit;
            font-size:.9rem;
            font-weight:600;
            cursor:pointer;
            transition:background .15s ease, transform .15s ease;
        }
        button:hover { background:var(--ddc-primary); }
        button:active { transform:translateY(1px); }
        button:disabled { opacity:.62; cursor:wait; }

        .status { margin:0; font-size:.9rem; font-weight:600; }
        .status.error { color:var(--danger); }
        .status.success { color:var(--success); }
        .error-summary {
            margin:0 0 26px;
            padding:14px 16px;
            color:#6B1F19;
            background:#FFF5F3;
            border-left:4px solid var(--danger);
        }
        .error-summary h2 { margin:0 0 6px; font-size:1rem; }
        .error-summary ul { margin:0; padding-left:20px; }
        noscript {
            display:block;
            margin:0 0 20px;
            padding:12px 14px;
            background:#FFF4E5;
            border-left:4px solid #F0B429;
        }
        .honeypot,.sr-only {
            position:absolute!important;
            left:-9999px!important;
            width:1px!important;
            height:1px!important;
            overflow:hidden!important;
        }

        @media (max-width:820px) {
            .photo-hero { height:310px; background-position:center center; }
            .application-shell { width:min(100% - 24px, 1065px); margin-top:-48px; }
            .intro-banner { min-height:128px; padding:25px 22px; }
            .form-panel { padding:36px 20px 34px; }
            .field.third { grid-column:span 6; }
        }

        @media (max-width:620px) {
            .photo-hero { height:235px; }
            .application-shell { width:100%; margin:-24px auto 0; }
            .intro-banner { min-height:124px; padding:24px 18px 22px; border-bottom-width:4px; }
            .intro-banner h1 { font-size:2rem; }
            .intro-banner p { margin-top:12px; }
            .form-panel { padding:32px 18px 36px; }
            fieldset { margin-bottom:30px; }
            legend { margin-bottom:18px; }
            .field,.field.third { grid-column:1/-1; }
            .grid { row-gap:15px; }
            .required-note { margin-bottom:24px; }
        }
    </style>
</head>
<body>
<div class="photo-hero" role="img" aria-label="Equipo de David Del Curto"></div>

<main class="application-shell">
    <header class="intro-banner">
        <h1>Entrega lo mejor de ti en DDC!</h1>
        <p>Postula para unirte al equipo</p>
    </header>

    <section class="form-panel" aria-labelledby="form-title">
        <div class="form-inner">
            <h2 id="form-title" class="sr-only">Formulario de postulación</h2>
            <noscript>Necesitas habilitar JavaScript para enviar este formulario.</noscript>
            <div id="error-summary" class="error-summary" role="alert" tabindex="-1" hidden></div>

            <form id="formulario" action="email.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="8388608">
                <div class="honeypot" aria-hidden="true">
                    <label for="website">Sitio web</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
                <p class="required-note">Los campos marcados con * son obligatorios.</p>

                <fieldset>
                    <legend>Información Personal</legend>
                    <div class="grid">
                        <div class="field"><label for="nombres">Nombre(s) *</label><input type="text" id="nombres" name="nombres" autocomplete="given-name" maxlength="80" required></div>
                        <div class="field"><label for="apellidos">Apellido(s) *</label><input type="text" id="apellidos" name="apellidos" autocomplete="family-name" maxlength="80" required></div>
                        <div class="field third"><label for="fechaDeNacimiento">Fecha de nacimiento *</label><input type="date" id="fechaDeNacimiento" name="fechaDeNacimiento" autocomplete="bday" required></div>
                        <div class="field third">
                            <label for="nacionalidad">Nacionalidad *</label>
                            <select id="nacionalidad" name="nacionalidad" required><option value="" selected disabled>Selecciona una opción</option><option value="Chilena">Chilena</option><option value="Extranjera">Extranjera</option></select>
                        </div>
                        <div class="field third">
                            <label for="rut">RUT <span id="rut-required-mark">*</span> <span class="optional" id="rut-help-label"></span></label>
                            <input type="text" id="rut" name="rut" autocomplete="off" inputmode="text" maxlength="12" placeholder="12.345.678-5">
                            <p class="help">Se valida el dígito verificador.</p>
                        </div>
                    </div>
                    <div id="foreign-fields" class="grid foreign-fields" hidden>
                        <div class="field"><label for="paisDeOrigen">País de origen *</label><input type="text" id="paisDeOrigen" name="paisDeOrigen" maxlength="80" autocomplete="country-name"></div>
                        <div class="field"><label for="numeroDePasaporte">N.º de pasaporte/documento *</label><input type="text" id="numeroDePasaporte" name="numeroDePasaporte" maxlength="40" autocomplete="off"></div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Información de contacto</legend>
                    <div class="grid">
                        <div class="field"><label for="direccionCalle">Dirección: Calle *</label><input type="text" id="direccionCalle" name="direccionCalle" autocomplete="address-line1" maxlength="120" required></div>
                        <div class="field third"><label for="direccionNumero">Número *</label><input type="text" id="direccionNumero" name="direccionNumero" maxlength="20" required></div>
                        <div class="field third"><label for="villaPoblacion">Villa / población <span class="optional">(opcional)</span></label><input type="text" id="villaPoblacion" name="villaPoblacion" autocomplete="address-line2" maxlength="120"></div>
                        <div class="field third"><label for="comuna">Comuna *</label><input type="text" id="comuna" name="comuna" autocomplete="address-level2" maxlength="80" required></div>
                        <div class="field">
                            <label for="region">Región *</label>
                            <select id="region" name="region" autocomplete="address-level1" required>
                                <option value="" selected disabled>Selecciona una región</option>
                                <option>Región de Arica y Parinacota</option><option>Región de Tarapacá</option><option>Región de Antofagasta</option><option>Región de Atacama</option><option>Región de Coquimbo</option><option>Región de Valparaíso</option><option>Región Metropolitana de Santiago</option><option>Región del Libertador General Bernardo O'Higgins</option><option>Región del Maule</option><option>Región de Ñuble</option><option>Región del Biobío</option><option>Región de La Araucanía</option><option>Región de Los Ríos</option><option>Región de Los Lagos</option><option>Región de Aysén del General Carlos Ibáñez del Campo</option><option>Región de Magallanes y de la Antártica Chilena</option>
                            </select>
                        </div>
                        <div class="field"><label for="celular">Teléfono Celular *</label><input type="tel" id="celular" name="celular" autocomplete="tel" maxlength="24" placeholder="+56 9 1234 5678" required></div>
                        <div class="field"><label for="email">Email *</label><input type="email" id="email" name="email" autocomplete="email" maxlength="120" required></div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Trabajo al que postula</legend>
                    <div class="grid">
                        <div class="field third"><label for="enQuePlantaDeseaTrabajar">¿En qué planta desea trabajar? *</label><select id="enQuePlantaDeseaTrabajar" name="enQuePlantaDeseaTrabajar" required><option value="" selected disabled>Selecciona una planta</option><option>Requínoa</option><option>Romeral</option><option>Retiro</option></select></div>
                        <div class="field third"><label for="temporadasTrabajadasEnDDC">Temporadas trabajadas en DDC *</label><select id="temporadasTrabajadasEnDDC" name="temporadasTrabajadasEnDDC" required><option value="" selected disabled>Selecciona una opción</option><option value="Nuevo">Primera temporada</option><option value="1">1 temporada</option><option value="2">2 temporadas</option><option value="3 o más">3 o más temporadas</option></select></div>
                        <div class="field third"><label for="trabajoAlQuePostula">Trabajo al que postula *</label><select id="trabajoAlQuePostula" name="trabajoAlQuePostula" required><option value="" selected disabled>Selecciona un cargo</option><option>Asistente administrativo</option><option>Camarero / lector / validador</option><option>Control de calidad</option><option>Digitador</option><option>Embaladora / selladora</option><option>Enzunchador</option><option>Movilizador de transpaleta</option><option>Operador de grúa horquilla</option><option>Operador de palletizador automático</option><option>Operador de Unitec</option><option>Operario de aseo / servicios generales</option><option>Operario de bodega / patio</option><option>Operario de centro de armado y altillo</option><option>Operario de frío / despacho</option><option>Operario de lavado de bandejas / totes</option><option>Operario de packing</option><option>Palletizador</option><option>Otro</option></select></div>
                        <div class="field third"><label for="disponibilidadDeTurnos">Disponibilidad de turnos *</label><select id="disponibilidadDeTurnos" name="disponibilidadDeTurnos" required><option value="" selected disabled>Selecciona una opción</option><option value="Día">Día</option><option value="Tarde">Tarde</option><option value="Ambos">Ambos</option></select></div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Ropa de trabajo</legend>
                    <p class="help section-help">Puedes elegir “Prefiero indicar después” si no conoces tu talla exacta.</p>
                    <div class="grid">
                        <div class="field third"><label for="tallaDePantalon">Talla de pantalón *</label><select id="tallaDePantalon" name="tallaDePantalon" required><option value="" selected disabled>Selecciona</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option><option>Prefiero indicar después</option></select></div>
                        <div class="field third"><label for="tallaDePolera">Talla de Polera *</label><select id="tallaDePolera" name="tallaDePolera" required><option value="" selected disabled>Selecciona</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option><option>Prefiero indicar después</option></select></div>
                        <div class="field third">
                            <label for="numeroDeCalzado">Número de Calzado *</label>
                            <select id="numeroDeCalzado" name="numeroDeCalzado" required>
                                <option value="" selected disabled>Selecciona</option>
                                <?php foreach (['35','35.5','36','36.5','37','37.5','38','38.5','39','39.5','40','40.5','41','41.5','42','42.5','43','43.5','44','45','46','Prefiero indicar después'] as $size): ?><option><?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Experiencia Laboral</legend>
                    <div class="grid">
                        <div class="field third"><label for="nivelEducacional">Nivel educacional *</label><select id="nivelEducacional" name="nivelEducacional" required><option value="" selected disabled>Selecciona una opción</option><option>Educación Básica</option><option>Educación Media</option><option>Educación Superior</option></select></div>
                        <div class="field"><label for="experienciasLaboralesPrevias">Experiencias laborales previas *</label><textarea id="experienciasLaboralesPrevias" name="experienciasLaboralesPrevias" maxlength="1500" required placeholder="Cuéntanos brevemente tus experiencias laborales más relevantes."></textarea></div>
                        <div class="field third"><label for="curriculum">Sube tu Currículum *</label><input type="file" id="curriculum" name="curriculum" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required><p class="help">PDF, DOC o DOCX. Máximo 8 MB.</p></div>
                        <div class="field third"><label for="comoSeEnteroDelTrabajo">¿Cómo se enteró del trabajo? *</label><select id="comoSeEnteroDelTrabajo" name="comoSeEnteroDelTrabajo" required><option value="" selected disabled>Selecciona una opción</option><option>Redes sociales</option><option>Recomendación de un conocido</option><option>Sitio web de DDC</option><option>Feria laboral</option><option>Municipalidad / OMIL</option><option>Otro</option></select></div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Privacidad</legend>
                    <div class="consent"><input type="checkbox" id="privacy_consent" name="privacy_consent" value="1" required><label for="privacy_consent">Autorizo el tratamiento de los datos ingresados y del currículum exclusivamente para gestionar mi postulación y procesos de selección relacionados. *</label></div>
                </fieldset>

                <div class="actions"><button type="submit" id="submit-button">Enviar postulación</button><p id="form-status" class="status" role="status" aria-live="polite"></p></div>
            </form>
        </div>
    </section>
</main>
<script src="scripts/formulario.js" defer></script>
</body>
</html>
