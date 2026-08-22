<?php
if (!defined('ABSPATH')) {
    exit;
}

$ddcPluginUrl = DDC_POSTULACIONES_URL;
$ddcWpContentMarker = '/wp-content/';
$ddcWpContentPos = strpos($ddcPluginUrl, $ddcWpContentMarker);
$ddcAjaxUrl = $ddcWpContentPos !== false
    ? substr($ddcPluginUrl, 0, $ddcWpContentPos) . '/wp-admin/admin-ajax.php'
    : admin_url('admin-ajax.php');
?>
<div class="ddc-postulaciones">
    <div class="ddc-photo-hero" role="img" aria-label="Equipo de David Del Curto"></div>

    <div class="ddc-application-shell">
        <header class="ddc-intro-banner">
            <h1>Entrega lo mejor de ti en DDC!</h1>
            <p>Postula para unirte al equipo</p>
        </header>

        <section class="ddc-form-panel">
            <div id="ddc-error-summary" class="ddc-error-summary" role="alert" tabindex="-1" hidden></div>

            <form id="ddc-postulaciones-form" action="<?php echo esc_url($ddcAjaxUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="ddc_submit_application">
                <?php wp_nonce_field('ddc_postulacion_submit', 'ddc_nonce'); ?>
                <input type="hidden" name="MAX_FILE_SIZE" value="8388608">

                <div class="ddc-honeypot" aria-hidden="true">
                    <label for="ddc-website">Sitio web</label>
                    <input type="text" id="ddc-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <p class="ddc-required-note">Los campos marcados con * son obligatorios.</p>

                <fieldset>
                    <legend>Información Personal</legend>
                    <div class="ddc-grid">
                        <div class="ddc-field ddc-half">
                            <label for="ddc-nombres">Nombre(s) *</label>
                            <input type="text" id="ddc-nombres" name="nombres" autocomplete="given-name" maxlength="80" required>
                        </div>
                        <div class="ddc-field ddc-half">
                            <label for="ddc-apellidos">Apellido(s) *</label>
                            <input type="text" id="ddc-apellidos" name="apellidos" autocomplete="family-name" maxlength="80" required>
                        </div>

                        <div class="ddc-field ddc-quarter">
                            <label for="ddc-rut">RUT <span id="ddc-rut-required">*</span></label>
                            <input type="text" id="ddc-rut" name="rut" maxlength="12" placeholder="12.345.678-5">
                        </div>
                        <div class="ddc-field ddc-quarter">
                            <label for="ddc-fecha">Fecha de nacimiento *</label>
                            <input type="date" id="ddc-fecha" name="fechaDeNacimiento" autocomplete="bday" required>
                        </div>
                        <div class="ddc-field ddc-quarter">
                            <label for="ddc-genero">Género *</label>
                            <select id="ddc-genero" name="genero" required>
                                <option value="" selected disabled>Selecciona</option>
                                <option>Femenino</option>
                                <option>Masculino</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-quarter">
                            <label for="ddc-estado-civil">Estado Civil *</label>
                            <select id="ddc-estado-civil" name="estadoCivil" required>
                                <option value="" selected disabled>Selecciona</option>
                                <option>Soltero</option>
                                <option>Casado</option>
                                <option>Viudo</option>
                                <option>Separado</option>
                            </select>
                        </div>

                        <div class="ddc-field ddc-third">
                            <label for="ddc-nacionalidad">Nacionalidad *</label>
                            <select id="ddc-nacionalidad" name="nacionalidad" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option value="Chilena">Chilena</option>
                                <option value="Extranjera">Extranjera</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third ddc-foreign-field" hidden>
                            <label for="ddc-pais">País de Origen *</label>
                            <input type="text" id="ddc-pais" name="paisDeOrigen" maxlength="80" autocomplete="country-name">
                        </div>
                        <div class="ddc-field ddc-third ddc-foreign-field" hidden>
                            <label for="ddc-pasaporte">N.º de Pasaporte / documento *</label>
                            <input type="text" id="ddc-pasaporte" name="numeroDePasaporte" maxlength="40">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Información de contacto</legend>
                    <div class="ddc-grid">
                        <div class="ddc-field ddc-third">
                            <label for="ddc-calle">Dirección: Calle *</label>
                            <input type="text" id="ddc-calle" name="direccionCalle" maxlength="120" autocomplete="address-line1" required>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-numero">Número *</label>
                            <input type="text" id="ddc-numero" name="direccionNumero" maxlength="20" required>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-villa">Villa / Población</label>
                            <input type="text" id="ddc-villa" name="villaPoblacion" maxlength="120" autocomplete="address-line2">
                        </div>

                        <div class="ddc-field ddc-third">
                            <label for="ddc-comuna">Comuna *</label>
                            <input type="text" id="ddc-comuna" name="comuna" maxlength="80" autocomplete="address-level2" required>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-region">Región *</label>
                            <select id="ddc-region" name="region" autocomplete="address-level1" required>
                                <option value="" selected disabled>Seleccionar una región</option>
                                <option>Región de Arica y Parinacota</option><option>Región de Tarapacá</option><option>Región de Antofagasta</option><option>Región de Atacama</option><option>Región de Coquimbo</option><option>Región de Valparaíso</option><option>Región Metropolitana de Santiago</option><option>Región del Libertador General Bernardo O'Higgins</option><option>Región del Maule</option><option>Región de Ñuble</option><option>Región del Biobío</option><option>Región de La Araucanía</option><option>Región de Los Ríos</option><option>Región de Los Lagos</option><option>Región de Aysén del General Carlos Ibáñez del Campo</option><option>Región de Magallanes y de la Antártica Chilena</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third"></div>

                        <div class="ddc-field ddc-half">
                            <label for="ddc-celular">Teléfono Celular *</label>
                            <input type="tel" id="ddc-celular" name="celular" maxlength="24" placeholder="+56 9 1234 5678" autocomplete="tel" required>
                        </div>
                        <div class="ddc-field ddc-half">
                            <label for="ddc-email">Email *</label>
                            <input type="email" id="ddc-email" name="email" maxlength="120" autocomplete="email" required>
                        </div>

                        <div class="ddc-field ddc-half">
                            <label for="ddc-contacto-emergencia">Contacto de emergencia (nombre completo)</label>
                            <input type="text" id="ddc-contacto-emergencia" name="contactoDeEmergencia" maxlength="120">
                        </div>
                        <div class="ddc-field ddc-half">
                            <label for="ddc-telefono-emergencia">Teléfono Contacto de Emergencia *</label>
                            <input type="tel" id="ddc-telefono-emergencia" name="telefonoContactoDeEmergencia" maxlength="24" placeholder="9 1234 5678" required>
                        </div>

                        <div class="ddc-field ddc-two-thirds">
                            <label for="ddc-diseno-calle">Diseño Calle</label>
                            <input type="text" id="ddc-diseno-calle" name="disenoCalle" maxlength="150">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Trabajo al que postula</legend>
                    <div class="ddc-grid">
                        <div class="ddc-field ddc-third">
                            <label for="ddc-planta">¿En qué planta desea trabajar? *</label>
                            <select id="ddc-planta" name="enQuePlantaDeseaTrabajar" required>
                                <option value="" selected disabled>Selecciona una planta</option>
                                <option>Requínoa</option><option>Romeral</option><option>Retiro</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-temporadas">Temporadas trabajadas en DDC *</label>
                            <select id="ddc-temporadas" name="temporadasTrabajadasEnDDC" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option value="Nuevo">Nuevo</option><option value="1">1</option><option value="2">2</option><option value="3 o más">3 o más</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-cargo">Trabajo al que postula *</label>
                            <select id="ddc-cargo" name="trabajoAlQuePostula" required>
                                <option value="" selected disabled>Selecciona un cargo</option>
                                <option>Asistente administrativo</option><option>Camarero / lector / validador</option><option>Control de calidad</option><option>Digitador</option><option>Embaladora / selladora</option><option>Enzunchador</option><option>Movilizador de transpaleta</option><option>Operador de grúa horquilla</option><option>Operador de palletizador automático</option><option>Operador de Unitec</option><option>Operario de aseo / servicios generales</option><option>Operario de bodega / patio</option><option>Operario de centro de armado y altillo</option><option>Operario de frío / despacho</option><option>Operario de lavado de bandejas / totes</option><option>Operario de packing</option><option>Palletizador</option><option>Otro</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-turnos">Disponibilidad de turnos *</label>
                            <select id="ddc-turnos" name="disponibilidadDeTurnos" required>
                                <option value="" selected disabled>Selecciona</option>
                                <option value="Día">Día</option><option value="Tarde">Tarde</option><option value="Ambos">Ambos</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Ropa de trabajo</legend>
                    <div class="ddc-grid">
                        <div class="ddc-field ddc-third">
                            <label for="ddc-talla-pantalon">Talla de pantalón *</label>
                            <select id="ddc-talla-pantalon" name="tallaDePantalon" required>
                                <option value="" selected disabled>Selecciona</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option><option>Prefiero indicar después</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-talla-polera">Talla de Polera *</label>
                            <select id="ddc-talla-polera" name="tallaDePolera" required>
                                <option value="" selected disabled>Selecciona</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option><option>Prefiero indicar después</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-calzado">Número de Calzado *</label>
                            <select id="ddc-calzado" name="numeroDeCalzado" required>
                                <option value="" selected disabled>Selecciona</option>
                                <?php foreach (['35','35.5','36','36.5','37','37.5','38','38.5','39','39.5','40','40.5','41','41.5','42','42.5','43','43.5','44','45','46','Prefiero indicar después'] as $size): ?>
                                    <option><?php echo esc_html($size); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Experiencia Laboral</legend>
                    <div class="ddc-grid">
                        <div class="ddc-field ddc-third">
                            <label for="ddc-educacion">Nivel educacional *</label>
                            <select id="ddc-educacion" name="nivelEducacional" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option>Educación Básica</option><option>Educación Media</option><option>Educación Superior</option>
                            </select>
                        </div>
                        <div class="ddc-field ddc-two-thirds">
                            <label for="ddc-experiencia">Experiencias laborales previas *</label>
                            <textarea id="ddc-experiencia" name="experienciasLaboralesPrevias" maxlength="1500" required></textarea>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-cv">Sube tu Currículum *</label>
                            <input type="file" id="ddc-cv" name="curriculum" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                            <p class="ddc-help">PDF, DOC o DOCX. Máximo 8 MB.</p>
                        </div>
                        <div class="ddc-field ddc-third">
                            <label for="ddc-origen">¿Cómo se enteró del trabajo? *</label>
                            <select id="ddc-origen" name="comoSeEnteroDelTrabajo" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option>Redes sociales</option><option>Recomendación de un conocido</option><option>Sitio web de DDC</option><option>Feria laboral</option><option>Municipalidad / OMIL</option><option>Otro</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Privacidad</legend>
                    <label class="ddc-consent" for="ddc-privacy-consent">
                        <input type="checkbox" id="ddc-privacy-consent" name="privacy_consent" value="1" required>
                        <span>Autorizo el tratamiento de los datos ingresados y del currículum exclusivamente para gestionar mi postulación y procesos de selección relacionados. *</span>
                    </label>
                </fieldset>

                <div class="ddc-actions">
                    <button type="submit" id="ddc-submit-button">Enviar postulación</button>
                    <p id="ddc-form-status" class="ddc-status" role="status" aria-live="polite"></p>
                </div>
            </form>
        </section>
    </div>
</div>
