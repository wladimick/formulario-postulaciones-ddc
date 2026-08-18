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
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Postula a DDC | David Del Curto</title>
    <style>
        :root { --brand:#066aab; --brand-dark:#04517f; --text:#18212a; --muted:#5c6873; --border:#cbd5df; --surface:#fff; --background:#f5f7f9; --danger:#b42318; --success:#067647; --focus:#ffbf47; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; color:var(--text); background:var(--background); line-height:1.5; }
        .page { max-width:980px; margin:0 auto; padding:32px 18px 64px; }
        .hero { margin-bottom:24px; }
        .hero h1 { margin:0 0 8px; font-size:clamp(1.9rem,4vw,2.8rem); }
        .hero p { margin:0; color:var(--muted); max-width:720px; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:clamp(18px,4vw,32px); box-shadow:0 8px 28px rgba(24,33,42,.06); }
        fieldset { border:0; margin:0 0 32px; padding:0; }
        legend { width:100%; font-size:1.25rem; font-weight:700; padding:0 0 12px; border-bottom:1px solid var(--border); margin-bottom:18px; }
        .grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:18px; }
        .field { grid-column:span 6; }
        .field.full { grid-column:1/-1; }
        .field.third { grid-column:span 4; }
        label { display:block; font-weight:650; margin-bottom:6px; }
        .optional { font-weight:400; color:var(--muted); font-size:.9rem; }
        input,select,textarea { width:100%; min-height:44px; border:1px solid var(--border); border-radius:8px; background:#fff; color:var(--text); padding:10px 12px; font:inherit; }
        textarea { min-height:110px; resize:vertical; }
        input:focus,select:focus,textarea:focus,button:focus-visible { outline:3px solid var(--focus); outline-offset:2px; border-color:var(--brand-dark); }
        [aria-invalid="true"] { border-color:var(--danger); }
        .help { margin:6px 0 0; color:var(--muted); font-size:.9rem; }
        .foreign-fields[hidden] { display:none!important; }
        .consent { display:flex; gap:10px; align-items:flex-start; padding:14px; background:#f8fafc; border:1px solid var(--border); border-radius:10px; }
        .consent input { width:auto; min-height:auto; margin-top:5px; }
        .consent label { font-weight:500; margin:0; }
        .actions { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        button { border:0; border-radius:9px; padding:12px 20px; background:var(--brand); color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        button:hover { background:var(--brand-dark); }
        button:disabled { opacity:.65; cursor:wait; }
        .status { margin:0; font-weight:650; }
        .status.error { color:var(--danger); }
        .status.success { color:var(--success); }
        .error-summary { margin:0 0 24px; padding:14px 16px; border-left:4px solid var(--danger); background:#fff4f2; border-radius:6px; }
        .error-summary h2 { margin:0 0 6px; font-size:1rem; }
        .error-summary ul { margin:0; padding-left:20px; }
        .honeypot,.sr-only { position:absolute!important; left:-9999px!important; width:1px!important; height:1px!important; overflow:hidden!important; }
        .required-note { color:var(--muted); font-size:.9rem; margin:0 0 22px; }
        .section-help { margin-top:-8px; margin-bottom:16px; }
        noscript { display:block; padding:12px; background:#fff4e5; border:1px solid #f0b429; border-radius:8px; margin-bottom:16px; }
        @media (max-width:720px) { .field,.field.third { grid-column:1/-1; } .page { padding-top:20px; } .card { border-radius:12px; } }
    </style>
</head>
<body>
<main class="page">
    <header class="hero">
        <h1>Entrega lo mejor de ti en DDC</h1>
        <p>Completa tus datos para postular. Te pedimos únicamente la información necesaria para gestionar esta etapa del proceso.</p>
    </header>

    <section class="card" aria-labelledby="form-title">
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
                <legend>Información personal</legend>
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
                    <div class="field"><label for="email">Email *</label><input type="email" id="email" name="email" autocomplete="email" maxlength="120" required></div>
                    <div class="field"><label for="celular">Teléfono celular *</label><input type="tel" id="celular" name="celular" autocomplete="tel" maxlength="24" placeholder="+56 9 1234 5678" required></div>
                    <div class="field third"><label for="direccionCalle">Calle *</label><input type="text" id="direccionCalle" name="direccionCalle" autocomplete="address-line1" maxlength="120" required></div>
                    <div class="field third"><label for="direccionNumero">Número *</label><input type="text" id="direccionNumero" name="direccionNumero" maxlength="20" required></div>
                    <div class="field third"><label for="villaPoblacion">Villa / población <span class="optional">(opcional)</span></label><input type="text" id="villaPoblacion" name="villaPoblacion" autocomplete="address-line2" maxlength="120"></div>
                    <div class="field"><label for="comuna">Comuna *</label><input type="text" id="comuna" name="comuna" autocomplete="address-level2" maxlength="80" required></div>
                    <div class="field">
                        <label for="region">Región *</label>
                        <select id="region" name="region" autocomplete="address-level1" required>
                            <option value="" selected disabled>Selecciona una región</option>
                            <option>Región de Arica y Parinacota</option><option>Región de Tarapacá</option><option>Región de Antofagasta</option><option>Región de Atacama</option><option>Región de Coquimbo</option><option>Región de Valparaíso</option><option>Región Metropolitana de Santiago</option><option>Región del Libertador General Bernardo O'Higgins</option><option>Región del Maule</option><option>Región de Ñuble</option><option>Región del Biobío</option><option>Región de La Araucanía</option><option>Región de Los Ríos</option><option>Región de Los Lagos</option><option>Región de Aysén del General Carlos Ibáñez del Campo</option><option>Región de Magallanes y de la Antártica Chilena</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Trabajo al que postula</legend>
                <div class="grid">
                    <div class="field third"><label for="enQuePlantaDeseaTrabajar">Planta *</label><select id="enQuePlantaDeseaTrabajar" name="enQuePlantaDeseaTrabajar" required><option value="" selected disabled>Selecciona una planta</option><option>Requínoa</option><option>Romeral</option><option>Retiro</option></select></div>
                    <div class="field third"><label for="temporadasTrabajadasEnDDC">Temporadas trabajadas en DDC *</label><select id="temporadasTrabajadasEnDDC" name="temporadasTrabajadasEnDDC" required><option value="" selected disabled>Selecciona una opción</option><option value="Nuevo">Primera temporada</option><option value="1">1 temporada</option><option value="2">2 temporadas</option><option value="3 o más">3 o más temporadas</option></select></div>
                    <div class="field third"><label for="disponibilidadDeTurnos">Disponibilidad de turnos *</label><select id="disponibilidadDeTurnos" name="disponibilidadDeTurnos" required><option value="" selected disabled>Selecciona una opción</option><option value="Día">Día</option><option value="Tarde">Tarde</option><option value="Ambos">Ambos</option></select></div>
                    <div class="field full">
                        <label for="trabajoAlQuePostula">Cargo *</label>
                        <select id="trabajoAlQuePostula" name="trabajoAlQuePostula" required>
                            <option value="" selected disabled>Selecciona un cargo</option>
                            <option>Asistente administrativo</option><option>Camarero / lector / validador</option><option>Control de calidad</option><option>Digitador</option><option>Embaladora / selladora</option><option>Enzunchador</option><option>Movilizador de transpaleta</option><option>Operador de grúa horquilla</option><option>Operador de palletizador automático</option><option>Operador de Unitec</option><option>Operario de aseo / servicios generales</option><option>Operario de bodega / patio</option><option>Operario de centro de armado y altillo</option><option>Operario de frío / despacho</option><option>Operario de lavado de bandejas / totes</option><option>Operario de packing</option><option>Palletizador</option><option>Otro</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Ropa de trabajo</legend>
                <p class="help section-help">Puedes elegir “Prefiero indicar después” si no conoces tu talla exacta.</p>
                <div class="grid">
                    <div class="field third"><label for="tallaDePantalon">Talla de pantalón *</label><select id="tallaDePantalon" name="tallaDePantalon" required><option value="" selected disabled>Selecciona</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option><option>Prefiero indicar después</option></select></div>
                    <div class="field third"><label for="tallaDePolera">Talla de polera *</label><select id="tallaDePolera" name="tallaDePolera" required><option value="" selected disabled>Selecciona</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option><option>Prefiero indicar después</option></select></div>
                    <div class="field third">
                        <label for="numeroDeCalzado">Número de calzado *</label>
                        <select id="numeroDeCalzado" name="numeroDeCalzado" required>
                            <option value="" selected disabled>Selecciona</option>
                            <?php foreach (['35','35.5','36','36.5','37','37.5','38','38.5','39','39.5','40','40.5','41','41.5','42','42.5','43','43.5','44','45','46','Prefiero indicar después'] as $size): ?><option><?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Experiencia y currículum</legend>
                <div class="grid">
                    <div class="field"><label for="nivelEducacional">Nivel educacional *</label><select id="nivelEducacional" name="nivelEducacional" required><option value="" selected disabled>Selecciona una opción</option><option>Educación Básica</option><option>Educación Media</option><option>Educación Superior</option></select></div>
                    <div class="field"><label for="comoSeEnteroDelTrabajo">¿Cómo te enteraste del trabajo? *</label><select id="comoSeEnteroDelTrabajo" name="comoSeEnteroDelTrabajo" required><option value="" selected disabled>Selecciona una opción</option><option>Redes sociales</option><option>Recomendación de un conocido</option><option>Sitio web de DDC</option><option>Feria laboral</option><option>Municipalidad / OMIL</option><option>Otro</option></select></div>
                    <div class="field full"><label for="experienciasLaboralesPrevias">Experiencias laborales previas *</label><textarea id="experienciasLaboralesPrevias" name="experienciasLaboralesPrevias" maxlength="1500" required placeholder="Cuéntanos brevemente tus experiencias laborales más relevantes."></textarea></div>
                    <div class="field full"><label for="curriculum">Currículum *</label><input type="file" id="curriculum" name="curriculum" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required><p class="help">Formatos permitidos: PDF, DOC o DOCX. Tamaño máximo: 8 MB.</p></div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Privacidad</legend>
                <div class="consent"><input type="checkbox" id="privacy_consent" name="privacy_consent" value="1" required><label for="privacy_consent">Autorizo el tratamiento de los datos ingresados y del currículum exclusivamente para gestionar mi postulación y procesos de selección relacionados. *</label></div>
            </fieldset>

            <div class="actions"><button type="submit" id="submit-button">Enviar postulación</button><p id="form-status" class="status" role="status" aria-live="polite"></p></div>
        </form>
    </section>
</main>
<script src="scripts/formulario.js" defer></script>
</body>
</html>
