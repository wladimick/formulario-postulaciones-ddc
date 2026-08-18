<?php
/**
 * Plugin Name: DDC Postulaciones
 * Description: Formulario de postulaciones laborales DDC integrado con WordPress y wp_mail().
 * Version: 1.0.0
 * Author: TIBOX
 * Text Domain: ddc-postulaciones
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('DDC_POSTULACIONES_VERSION', '1.0.0');
define('DDC_POSTULACIONES_FILE', __FILE__);
define('DDC_POSTULACIONES_DIR', plugin_dir_path(__FILE__));
define('DDC_POSTULACIONES_URL', plugin_dir_url(__FILE__));
define('DDC_POSTULACIONES_MAX_CV_BYTES', 8 * 1024 * 1024);

require_once DDC_POSTULACIONES_DIR . 'includes/email-template.php';

register_activation_hook(__FILE__, static function (): void {
    if (get_option('ddc_postulaciones_recipient', '') === '') {
        update_option('ddc_postulaciones_recipient', (string) get_option('admin_email'));
    }

    if (get_option('ddc_postulaciones_subject', '') === '') {
        update_option('ddc_postulaciones_subject', 'Postulación DDC - {nombre} - {cargo}');
    }
});

add_action('admin_init', static function (): void {
    register_setting('ddc_postulaciones_settings', 'ddc_postulaciones_recipient', [
        'type' => 'string',
        'sanitize_callback' => static function ($value): string {
            $email = sanitize_email((string) $value);
            return is_email($email) ? $email : '';
        },
        'default' => (string) get_option('admin_email'),
    ]);

    register_setting('ddc_postulaciones_settings', 'ddc_postulaciones_subject', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'Postulación DDC - {nombre} - {cargo}',
    ]);
});

add_action('admin_menu', static function (): void {
    add_options_page(
        'Postulaciones DDC',
        'Postulaciones DDC',
        'manage_options',
        'ddc-postulaciones',
        'ddc_postulaciones_render_settings_page'
    );
});

function ddc_postulaciones_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $recipient = (string) get_option('ddc_postulaciones_recipient', get_option('admin_email'));
    $subject = (string) get_option('ddc_postulaciones_subject', 'Postulación DDC - {nombre} - {cargo}');
    ?>
    <div class="wrap">
        <h1>Postulaciones DDC</h1>
        <p>El envío utiliza <code>wp_mail()</code>. Si WP Mail SMTP está activo, este formulario usa automáticamente el proveedor configurado allí (por ejemplo SendGrid).</p>

        <form method="post" action="options.php">
            <?php settings_fields('ddc_postulaciones_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ddc_postulaciones_recipient">Destinatario</label></th>
                    <td>
                        <input type="email" class="regular-text" id="ddc_postulaciones_recipient" name="ddc_postulaciones_recipient" value="<?php echo esc_attr($recipient); ?>" required>
                        <p class="description">Casilla que recibe las postulaciones. Para pruebas puedes usar tu correo y luego cambiarlo por RR.HH.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ddc_postulaciones_subject">Asunto del correo</label></th>
                    <td>
                        <input type="text" class="regular-text" id="ddc_postulaciones_subject" name="ddc_postulaciones_subject" value="<?php echo esc_attr($subject); ?>" required>
                        <p class="description">Variables disponibles: <code>{nombre}</code>, <code>{cargo}</code>, <code>{planta}</code>, <code>{rut}</code>.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar cambios'); ?>
        </form>

        <hr>
        <h2>Uso</h2>
        <p>Crea o edita una página de WordPress y agrega este shortcode:</p>
        <p><code>[ddc_formulario_postulacion]</code></p>
    </div>
    <?php
}

add_shortcode('ddc_formulario_postulacion', static function (): string {
    wp_enqueue_style(
        'ddc-postulaciones-font',
        'https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;500;600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'ddc-postulaciones',
        DDC_POSTULACIONES_URL . 'assets/formulario.css',
        [],
        DDC_POSTULACIONES_VERSION
    );
    wp_enqueue_script(
        'ddc-postulaciones',
        DDC_POSTULACIONES_URL . 'assets/formulario.js',
        [],
        DDC_POSTULACIONES_VERSION,
        true
    );
    wp_localize_script('ddc-postulaciones', 'DDCPostulaciones', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'maxFileBytes' => DDC_POSTULACIONES_MAX_CV_BYTES,
    ]);

    ob_start();
    require DDC_POSTULACIONES_DIR . 'includes/form.php';
    return (string) ob_get_clean();
});

add_action('wp_ajax_nopriv_ddc_submit_application', 'ddc_postulaciones_handle_submission');
add_action('wp_ajax_ddc_submit_application', 'ddc_postulaciones_handle_submission');

add_action('wp_mail_failed', static function (WP_Error $error): void {
    error_log('DDC Postulaciones - wp_mail_failed: ' . $error->get_error_message());
});

function ddc_postulaciones_handle_submission(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
        wp_send_json_error(['message' => 'Método no permitido.'], 405);
    }

    if (!check_ajax_referer('ddc_postulacion_submit', 'ddc_nonce', false)) {
        wp_send_json_error(['message' => 'La sesión del formulario expiró. Recarga la página e intenta nuevamente.'], 403);
    }

    if (ddc_postulaciones_post_text('website', 200) !== '') {
        wp_send_json_success(['message' => 'Postulación recibida correctamente.']);
    }

    if (!ddc_postulaciones_rate_limit()) {
        wp_send_json_error(['message' => 'Has realizado demasiados intentos. Espera unos minutos antes de volver a enviar.'], 429);
    }

    $regions = [
        'Región de Arica y Parinacota', 'Región de Tarapacá', 'Región de Antofagasta', 'Región de Atacama',
        'Región de Coquimbo', 'Región de Valparaíso', 'Región Metropolitana de Santiago',
        "Región del Libertador General Bernardo O'Higgins", 'Región del Maule', 'Región de Ñuble',
        'Región del Biobío', 'Región de La Araucanía', 'Región de Los Ríos', 'Región de Los Lagos',
        'Región de Aysén del General Carlos Ibáñez del Campo', 'Región de Magallanes y de la Antártica Chilena',
    ];
    $genders = ['Femenino', 'Masculino'];
    $civilStatuses = ['Soltero', 'Casado', 'Viudo', 'Separado'];
    $plants = ['Requínoa', 'Romeral', 'Retiro'];
    $seasons = ['Nuevo', '1', '2', '3 o más'];
    $jobs = [
        'Asistente administrativo', 'Camarero / lector / validador', 'Control de calidad', 'Digitador',
        'Embaladora / selladora', 'Enzunchador', 'Movilizador de transpaleta', 'Operador de grúa horquilla',
        'Operador de palletizador automático', 'Operador de Unitec', 'Operario de aseo / servicios generales',
        'Operario de bodega / patio', 'Operario de centro de armado y altillo', 'Operario de frío / despacho',
        'Operario de lavado de bandejas / totes', 'Operario de packing', 'Palletizador', 'Otro',
    ];
    $shifts = ['Día', 'Tarde', 'Ambos'];
    $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL', 'Prefiero indicar después'];
    $shoeSizes = ['35','35.5','36','36.5','37','37.5','38','38.5','39','39.5','40','40.5','41','41.5','42','42.5','43','43.5','44','45','46','Prefiero indicar después'];
    $education = ['Educación Básica', 'Educación Media', 'Educación Superior'];
    $sources = ['Redes sociales', 'Recomendación de un conocido', 'Sitio web de DDC', 'Feria laboral', 'Municipalidad / OMIL', 'Otro'];

    $data = [
        'nombres' => ddc_postulaciones_post_text('nombres', 80),
        'apellidos' => ddc_postulaciones_post_text('apellidos', 80),
        'rut' => ddc_postulaciones_post_text('rut', 12),
        'fechaDeNacimiento' => ddc_postulaciones_post_text('fechaDeNacimiento', 10),
        'genero' => ddc_postulaciones_post_text('genero', 20),
        'estadoCivil' => ddc_postulaciones_post_text('estadoCivil', 20),
        'nacionalidad' => ddc_postulaciones_post_text('nacionalidad', 20),
        'paisDeOrigen' => ddc_postulaciones_post_text('paisDeOrigen', 80),
        'numeroDePasaporte' => ddc_postulaciones_post_text('numeroDePasaporte', 40),
        'direccionCalle' => ddc_postulaciones_post_text('direccionCalle', 120),
        'direccionNumero' => ddc_postulaciones_post_text('direccionNumero', 20),
        'villaPoblacion' => ddc_postulaciones_post_text('villaPoblacion', 120),
        'comuna' => ddc_postulaciones_post_text('comuna', 80),
        'region' => ddc_postulaciones_post_text('region', 100),
        'celular' => ddc_postulaciones_post_text('celular', 24),
        'email' => sanitize_email(ddc_postulaciones_post_text('email', 120)),
        'contactoDeEmergencia' => ddc_postulaciones_post_text('contactoDeEmergencia', 120),
        'telefonoContactoDeEmergencia' => ddc_postulaciones_post_text('telefonoContactoDeEmergencia', 24),
        'disenoCalle' => ddc_postulaciones_post_text('disenoCalle', 150),
        'enQuePlantaDeseaTrabajar' => ddc_postulaciones_post_text('enQuePlantaDeseaTrabajar', 30),
        'temporadasTrabajadasEnDDC' => ddc_postulaciones_post_text('temporadasTrabajadasEnDDC', 20),
        'trabajoAlQuePostula' => ddc_postulaciones_post_text('trabajoAlQuePostula', 100),
        'disponibilidadDeTurnos' => ddc_postulaciones_post_text('disponibilidadDeTurnos', 20),
        'tallaDePantalon' => ddc_postulaciones_post_text('tallaDePantalon', 30),
        'tallaDePolera' => ddc_postulaciones_post_text('tallaDePolera', 30),
        'numeroDeCalzado' => ddc_postulaciones_post_text('numeroDeCalzado', 30),
        'nivelEducacional' => ddc_postulaciones_post_text('nivelEducacional', 40),
        'experienciasLaboralesPrevias' => ddc_postulaciones_post_textarea('experienciasLaboralesPrevias', 1500),
        'comoSeEnteroDelTrabajo' => ddc_postulaciones_post_text('comoSeEnteroDelTrabajo', 80),
    ];

    $errors = [];
    $required = [
        'nombres' => 'Ingresa tu nombre.', 'apellidos' => 'Ingresa tus apellidos.',
        'fechaDeNacimiento' => 'Ingresa tu fecha de nacimiento.', 'genero' => 'Selecciona tu género.',
        'estadoCivil' => 'Selecciona tu estado civil.', 'nacionalidad' => 'Selecciona tu nacionalidad.',
        'direccionCalle' => 'Ingresa la calle de tu dirección.', 'direccionNumero' => 'Ingresa el número de tu dirección.',
        'comuna' => 'Ingresa tu comuna.', 'region' => 'Selecciona tu región.',
        'celular' => 'Ingresa tu teléfono celular.', 'email' => 'Ingresa tu email.',
        'telefonoContactoDeEmergencia' => 'Ingresa el teléfono del contacto de emergencia.',
        'enQuePlantaDeseaTrabajar' => 'Selecciona la planta donde deseas trabajar.',
        'temporadasTrabajadasEnDDC' => 'Indica cuántas temporadas has trabajado en DDC.',
        'trabajoAlQuePostula' => 'Selecciona el cargo al que postulas.',
        'disponibilidadDeTurnos' => 'Selecciona tu disponibilidad de turnos.',
        'tallaDePantalon' => 'Selecciona una opción para talla de pantalón.',
        'tallaDePolera' => 'Selecciona una opción para talla de polera.',
        'numeroDeCalzado' => 'Selecciona una opción para número de calzado.',
        'nivelEducacional' => 'Selecciona tu nivel educacional.',
        'experienciasLaboralesPrevias' => 'Describe brevemente tu experiencia laboral.',
        'comoSeEnteroDelTrabajo' => 'Indica cómo te enteraste del trabajo.',
    ];

    foreach ($required as $field => $message) {
        if ($data[$field] === '') {
            $errors[$field] = $message;
        }
    }

    if ($data['nacionalidad'] === 'Chilena') {
        if ($data['rut'] === '') {
            $errors['rut'] = 'Ingresa tu RUT.';
        } elseif (!ddc_postulaciones_valid_rut($data['rut'])) {
            $errors['rut'] = 'El RUT ingresado no es válido.';
        }
    } elseif ($data['nacionalidad'] === 'Extranjera') {
        if ($data['rut'] !== '' && !ddc_postulaciones_valid_rut($data['rut'])) {
            $errors['rut'] = 'El RUT ingresado no es válido.';
        }
        if ($data['paisDeOrigen'] === '') {
            $errors['paisDeOrigen'] = 'Ingresa tu país de origen.';
        }
        if ($data['numeroDePasaporte'] === '') {
            $errors['numeroDePasaporte'] = 'Ingresa tu número de pasaporte o documento.';
        }
    } elseif ($data['nacionalidad'] !== '') {
        $errors['nacionalidad'] = 'La nacionalidad seleccionada no es válida.';
    }

    if ($data['email'] !== '' && !is_email($data['email'])) {
        $errors['email'] = 'Ingresa un email válido.';
    }
    if ($data['celular'] !== '' && !ddc_postulaciones_valid_phone($data['celular'])) {
        $errors['celular'] = 'Ingresa un teléfono celular válido.';
    }
    if ($data['telefonoContactoDeEmergencia'] !== '' && !ddc_postulaciones_valid_phone($data['telefonoContactoDeEmergencia'])) {
        $errors['telefonoContactoDeEmergencia'] = 'Ingresa un teléfono de contacto de emergencia válido.';
    }

    if ($data['fechaDeNacimiento'] !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['fechaDeNacimiento']);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $invalid = $date === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0));
        if ($invalid || $date > new DateTimeImmutable('today') || $date < new DateTimeImmutable('1900-01-01')) {
            $errors['fechaDeNacimiento'] = 'Ingresa una fecha de nacimiento válida.';
        }
    }

    $enumChecks = [
        'genero' => $genders, 'estadoCivil' => $civilStatuses, 'region' => $regions,
        'enQuePlantaDeseaTrabajar' => $plants, 'temporadasTrabajadasEnDDC' => $seasons,
        'trabajoAlQuePostula' => $jobs, 'disponibilidadDeTurnos' => $shifts,
        'tallaDePantalon' => $clothingSizes, 'tallaDePolera' => $clothingSizes,
        'numeroDeCalzado' => $shoeSizes, 'nivelEducacional' => $education,
        'comoSeEnteroDelTrabajo' => $sources,
    ];
    foreach ($enumChecks as $field => $allowed) {
        if ($data[$field] !== '' && !in_array($data[$field], $allowed, true)) {
            $errors[$field] = 'La opción seleccionada no es válida.';
        }
    }

    if ((string) ($_POST['privacy_consent'] ?? '') !== '1') {
        $errors['privacy_consent'] = 'Debes autorizar el tratamiento de tus datos para enviar la postulación.';
    }

    $cv = ddc_postulaciones_validate_cv($errors);

    if ($errors !== []) {
        wp_send_json_error(['message' => 'Revisa los datos ingresados.', 'errors' => $errors], 422);
    }

    $recipient = sanitize_email((string) get_option('ddc_postulaciones_recipient', get_option('admin_email')));
    if (!is_email($recipient)) {
        wp_send_json_error(['message' => 'El destinatario del formulario no está configurado correctamente.'], 500);
    }

    $subjectTemplate = (string) get_option('ddc_postulaciones_subject', 'Postulación DDC - {nombre} - {cargo}');
    $subject = strtr($subjectTemplate, [
        '{nombre}' => trim($data['nombres'] . ' ' . $data['apellidos']),
        '{cargo}' => $data['trabajoAlQuePostula'],
        '{planta}' => $data['enQuePlantaDeseaTrabajar'],
        '{rut}' => $data['rut'],
    ]);
    $subject = sanitize_text_field($subject);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        sprintf('Reply-To: %s <%s>', sanitize_text_field(trim($data['nombres'] . ' ' . $data['apellidos'])), $data['email']),
    ];

    $body = ddc_postulaciones_build_email_body($data);
    $sent = wp_mail($recipient, $subject, $body, $headers, [$cv['tmp_name']]);

    if (!$sent) {
        wp_send_json_error(['message' => 'No pudimos enviar tu postulación en este momento. Intenta nuevamente más tarde.'], 502);
    }

    wp_send_json_success(['message' => 'Tu postulación fue enviada correctamente. Gracias por tu interés en DDC.']);
}

function ddc_postulaciones_post_text(string $key, int $maxLength = 255): string
{
    $value = isset($_POST[$key]) && is_string($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
    $value = sanitize_text_field($value);
    return mb_substr(trim($value), 0, $maxLength);
}

function ddc_postulaciones_post_textarea(string $key, int $maxLength = 1500): string
{
    $value = isset($_POST[$key]) && is_string($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
    $value = sanitize_textarea_field($value);
    return mb_substr(trim($value), 0, $maxLength);
}

function ddc_postulaciones_valid_phone(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    return strlen($digits) >= 8 && strlen($digits) <= 15;
}

function ddc_postulaciones_valid_rut(string $rut): bool
{
    $normalized = strtoupper(preg_replace('/[^0-9K]/i', '', $rut) ?? '');
    if (!preg_match('/^(\d{7,8})([0-9K])$/', $normalized, $matches)) {
        return false;
    }

    $number = $matches[1];
    $sum = 0;
    $multiplier = 2;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $sum += (int) $number[$i] * $multiplier;
        $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
    }
    $result = 11 - ($sum % 11);
    $expected = $result === 11 ? '0' : ($result === 10 ? 'K' : (string) $result);
    return hash_equals($expected, $matches[2]);
}

function ddc_postulaciones_validate_cv(array &$errors): ?array
{
    if (!isset($_FILES['curriculum']) || !is_array($_FILES['curriculum'])) {
        $errors['curriculum'] = 'Debes adjuntar tu currículum.';
        return null;
    }

    $file = $_FILES['curriculum'];
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $errors['curriculum'] = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'El currículum supera el tamaño máximo de 8 MB.'
            : 'No fue posible recibir el currículum.';
        return null;
    }

    $size = (int) ($file['size'] ?? 0);
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = basename((string) ($file['name'] ?? 'curriculum'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($size <= 0 || $size > DDC_POSTULACIONES_MAX_CV_BYTES) {
        $errors['curriculum'] = 'El currículum debe pesar entre 1 byte y 8 MB.';
        return null;
    }
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $errors['curriculum'] = 'El archivo recibido no es una carga válida.';
        return null;
    }
    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        $errors['curriculum'] = 'El currículum debe ser PDF, DOC o DOCX.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName) ?: 'application/octet-stream';
    $allowed = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];
    if (!in_array($mime, $allowed[$extension], true)) {
        $errors['curriculum'] = 'El tipo real del archivo no coincide con un PDF, DOC o DOCX permitido.';
        return null;
    }

    return ['tmp_name' => $tmpName, 'mime' => $mime];
}

function ddc_postulaciones_rate_limit(): bool
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = 'ddc_apply_' . md5($ip);
    $attempts = (int) get_transient($key);
    if ($attempts >= 5) {
        return false;
    }
    set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
    return true;
}
