<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

const MAX_CV_BYTES = 8 * 1024 * 1024;
const RATE_LIMIT_ATTEMPTS = 5;
const RATE_LIMIT_WINDOW_SECONDS = 900;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cleanString(string $key, int $maxLength = 255): string
{
    $value = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    $value = trim(str_replace("\0", '', $value));
    if (mb_strlen($value) > $maxLength) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validRut(string $rut): bool
{
    $normalized = strtoupper(preg_replace('/[^0-9K]/i', '', $rut) ?? '');
    if (!preg_match('/^(\d{7,8})([0-9K])$/', $normalized, $matches)) {
        return false;
    }

    $number = $matches[1];
    $providedDv = $matches[2];
    $sum = 0;
    $multiplier = 2;

    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $sum += (int)$number[$i] * $multiplier;
        $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
    }

    $result = 11 - ($sum % 11);
    $expectedDv = $result === 11 ? '0' : ($result === 10 ? 'K' : (string)$result);
    return hash_equals($expectedDv, $providedDv);
}

function validPhone(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    return strlen($digits) >= 8 && strlen($digits) <= 15;
}

function allowedValue(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

function enforceRateLimit(string $key): void
{
    $file = sys_get_temp_dir() . '/ddc-form-rate-' . hash('sha256', $key) . '.json';
    $now = time();
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        return;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return;
        }

        $raw = stream_get_contents($handle);
        $data = $raw ? json_decode($raw, true) : null;
        $attempts = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
        $attempts = array_values(array_filter(
            $attempts,
            static fn($ts) => is_int($ts) && $ts > $now - RATE_LIMIT_WINDOW_SECONDS
        ));

        if (count($attempts) >= RATE_LIMIT_ATTEMPTS) {
            respond(429, [
                'ok' => false,
                'message' => 'Has realizado demasiados intentos. Espera unos minutos antes de volver a enviar.',
            ]);
        }

        $attempts[] = $now;
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode(['attempts' => $attempts]));
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

function validateCv(array &$errors): ?array
{
    if (!isset($_FILES['curriculum']) || !is_array($_FILES['curriculum'])) {
        $errors['curriculum'] = 'Debes adjuntar tu currículum.';
        return null;
    }

    $file = $_FILES['curriculum'];
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $errors['curriculum'] = $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
            ? 'El currículum supera el tamaño máximo de 8 MB.'
            : 'No fue posible recibir el currículum.';
        return null;
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > MAX_CV_BYTES) {
        $errors['curriculum'] = 'El currículum debe pesar entre 1 byte y 8 MB.';
        return null;
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $errors['curriculum'] = 'El archivo recibido no es una carga válida.';
        return null;
    }

    $originalName = basename((string)($file['name'] ?? 'curriculum'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $errors['curriculum'] = 'El currículum debe ser PDF, DOC o DOCX.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName) ?: 'application/octet-stream';
    $allowedMimes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    if (!in_array($mime, $allowedMimes[$extension], true)) {
        $errors['curriculum'] = 'El tipo real del archivo no coincide con un PDF, DOC o DOCX permitido.';
        return null;
    }

    return [
        'tmp_name' => $tmpName,
        'safe_name' => 'CV_' . date('Ymd_His') . '.' . $extension,
        'mime' => $mime,
    ];
}

function emailRows(array $rows): string
{
    $html = '';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="padding:12px 14px;border-bottom:1px solid #E8EEF7;width:38%;vertical-align:top;color:#606060;font-size:13px;font-weight:600;line-height:1.4;">'
            . escape((string)$label)
            . '</td>'
            . '<td style="padding:12px 14px;border-bottom:1px solid #E8EEF7;vertical-align:top;color:#2C2E65;font-size:14px;font-weight:400;line-height:1.5;word-break:break-word;">'
            . nl2br(escape((string)$value))
            . '</td>'
            . '</tr>';
    }
    return $html;
}

function emailSection(string $title, array $rows): string
{
    return '<tr><td style="padding:0 24px 22px 24px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;background:#FFFFFF;border:1px solid #DCE7F7;border-radius:10px;overflow:hidden;">'
        . '<tr><td colspan="2" style="padding:13px 16px;background:#F2F7FF;border-bottom:3px solid #3CB4E5;color:#003DA6;font-size:16px;font-weight:600;line-height:1.3;">'
        . escape($title)
        . '</td></tr>'
        . emailRows($rows)
        . '</table></td></tr>';
}

function buildEmailBody(array $data): string
{
    $fullName = trim($data['nombres'] . ' ' . $data['apellidos']);
    $dateLabel = $data['fechaDeNacimiento'];
    $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['fechaDeNacimiento']);
    if ($birthDate instanceof DateTimeImmutable) {
        $dateLabel = $birthDate->format('d/m/Y');
    }

    $personal = [
        'Nombre completo' => $fullName,
        'Fecha de nacimiento' => $dateLabel,
        'Nacionalidad' => $data['nacionalidad'],
        'RUT' => $data['rut'] ?: 'No informado',
    ];
    if ($data['nacionalidad'] === 'Extranjera') {
        $personal['País de origen'] = $data['paisDeOrigen'];
        $personal['Pasaporte / documento'] = $data['numeroDePasaporte'];
    }

    $contact = [
        'Email' => $data['email'],
        'Celular' => $data['celular'],
        'Dirección' => trim($data['direccionCalle'] . ' ' . $data['direccionNumero']),
        'Villa / población' => $data['villaPoblacion'] ?: 'No informado',
        'Comuna' => $data['comuna'],
        'Región' => $data['region'],
    ];

    $application = [
        'Planta' => $data['enQuePlantaDeseaTrabajar'],
        'Cargo al que postula' => $data['trabajoAlQuePostula'],
        'Disponibilidad de turnos' => $data['disponibilidadDeTurnos'],
        'Temporadas trabajadas en DDC' => $data['temporadasTrabajadasEnDDC'],
        'Cómo se enteró' => $data['comoSeEnteroDelTrabajo'],
    ];

    $profile = [
        'Nivel educacional' => $data['nivelEducacional'],
        'Experiencia laboral' => $data['experienciasLaboralesPrevias'],
    ];

    $equipment = [
        'Talla de pantalón' => $data['tallaDePantalon'],
        'Talla de polera' => $data['tallaDePolera'],
        'Número de calzado' => $data['numeroDeCalzado'],
    ];

    $preheader = 'Nueva postulación para ' . $data['trabajoAlQuePostula'] . ' en ' . $data['enQuePlantaDeseaTrabajar'] . '.';

    return '<!doctype html>'
        . '<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Nueva postulación DDC</title></head>'
        . '<body style="margin:0;padding:0;background:#F4F6F8;font-family:\'Titillium Web\',Arial,Helvetica,sans-serif;color:#606060;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . escape($preheader) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#F4F6F8;border-collapse:collapse;">'
        . '<tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;background:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 8px 28px rgba(44,46,101,.10);">'

        . '<tr><td style="padding:0;background:#003DA6;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td style="padding:24px 28px 20px 28px;vertical-align:middle;">'
        . '<div style="font-size:34px;line-height:1;color:#FFFFFF;font-weight:600;letter-spacing:-1px;">DDC<span style="color:#3CB4E5;">.</span></div>'
        . '<div style="padding-top:5px;color:#BFD6F7;font-size:12px;letter-spacing:.7px;text-transform:uppercase;">David Del Curto · Personas</div>'
        . '</td>'
        . '<td align="right" style="padding:24px 28px 20px 12px;vertical-align:middle;color:#FFFFFF;font-size:12px;font-weight:600;">NUEVA POSTULACIÓN</td>'
        . '</tr><tr><td colspan="2" style="height:5px;background:#00C7B1;font-size:0;line-height:0;">&nbsp;</td></tr></table>'
        . '</td></tr>'

        . '<tr><td style="padding:30px 28px 12px 28px;">'
        . '<div style="color:#606060;font-size:13px;font-weight:500;text-transform:uppercase;letter-spacing:.8px;">Postulación recibida</div>'
        . '<div style="padding-top:4px;color:#003DA6;font-size:27px;line-height:1.2;font-weight:600;">' . escape($fullName) . '</div>'
        . '<div style="padding-top:8px;color:#2C2E65;font-size:16px;line-height:1.45;">Postula a <strong>' . escape($data['trabajoAlQuePostula']) . '</strong> en la planta <strong>' . escape($data['enQuePlantaDeseaTrabajar']) . '</strong>.</div>'
        . '</td></tr>'

        . '<tr><td style="padding:8px 28px 26px 28px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF6FF;border-radius:10px;border-left:4px solid #0662FF;">'
        . '<tr><td style="padding:14px 16px;color:#2C2E65;font-size:13px;line-height:1.5;">'
        . '<strong style="color:#003DA6;">Currículum adjunto.</strong> Puedes responder directamente a este correo para contactar a ' . escape($data['nombres']) . '; el Reply-To apunta a <strong>' . escape($data['email']) . '</strong>.'
        . '</td></tr></table></td></tr>'

        . emailSection('Información personal', $personal)
        . emailSection('Contacto y ubicación', $contact)
        . emailSection('Postulación', $application)
        . emailSection('Formación y experiencia', $profile)
        . emailSection('Ropa de trabajo', $equipment)

        . '<tr><td style="padding:0 28px 28px 28px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFD;border-radius:8px;">'
        . '<tr><td style="padding:13px 15px;color:#606060;font-size:11px;line-height:1.5;">La persona postulante declaró autorizar el tratamiento de estos datos para gestionar su postulación laboral. Este correo puede contener información personal; úsala únicamente para fines del proceso de selección.</td></tr>'
        . '</table></td></tr>'

        . '<tr><td style="padding:20px 28px;background:#2C2E65;text-align:center;">'
        . '<div style="color:#FFFFFF;font-size:13px;font-weight:600;">David Del Curto S.A.</div>'
        . '<div style="padding-top:4px;color:#BFD6F7;font-size:11px;line-height:1.5;">Postulaciones · Entregar buenos frutos</div>'
        . '</td></tr>'

        . '</table></td></tr></table></body></html>';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Método no permitido.']);
}

$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
if ($contentLength > MAX_CV_BYTES + 1024 * 1024) {
    respond(413, ['ok' => false, 'message' => 'La solicitud supera el tamaño permitido.']);
}

$sessionToken = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
$postedToken = cleanString('csrf_token', 128);
if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
    respond(403, ['ok' => false, 'message' => 'La sesión del formulario expiró. Recarga la página e intenta nuevamente.']);
}

if (cleanString('website', 200) !== '') {
    respond(200, ['ok' => true, 'message' => 'Postulación recibida correctamente.', 'csrf_token' => $sessionToken]);
}

$remoteAddress = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
enforceRateLimit($remoteAddress);
enforceRateLimit(session_id());

$regions = [
    'Región de Arica y Parinacota', 'Región de Tarapacá', 'Región de Antofagasta', 'Región de Atacama',
    'Región de Coquimbo', 'Región de Valparaíso', 'Región Metropolitana de Santiago',
    "Región del Libertador General Bernardo O'Higgins", 'Región del Maule', 'Región de Ñuble',
    'Región del Biobío', 'Región de La Araucanía', 'Región de Los Ríos', 'Región de Los Lagos',
    'Región de Aysén del General Carlos Ibáñez del Campo', 'Región de Magallanes y de la Antártica Chilena',
];
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
    'nombres' => cleanString('nombres', 80),
    'apellidos' => cleanString('apellidos', 80),
    'fechaDeNacimiento' => cleanString('fechaDeNacimiento', 10),
    'nacionalidad' => cleanString('nacionalidad', 20),
    'rut' => cleanString('rut', 12),
    'paisDeOrigen' => cleanString('paisDeOrigen', 80),
    'numeroDePasaporte' => cleanString('numeroDePasaporte', 40),
    'email' => cleanString('email', 120),
    'celular' => cleanString('celular', 24),
    'direccionCalle' => cleanString('direccionCalle', 120),
    'direccionNumero' => cleanString('direccionNumero', 20),
    'villaPoblacion' => cleanString('villaPoblacion', 120),
    'comuna' => cleanString('comuna', 80),
    'region' => cleanString('region', 100),
    'enQuePlantaDeseaTrabajar' => cleanString('enQuePlantaDeseaTrabajar', 30),
    'temporadasTrabajadasEnDDC' => cleanString('temporadasTrabajadasEnDDC', 20),
    'trabajoAlQuePostula' => cleanString('trabajoAlQuePostula', 100),
    'disponibilidadDeTurnos' => cleanString('disponibilidadDeTurnos', 20),
    'tallaDePantalon' => cleanString('tallaDePantalon', 30),
    'tallaDePolera' => cleanString('tallaDePolera', 30),
    'numeroDeCalzado' => cleanString('numeroDeCalzado', 30),
    'nivelEducacional' => cleanString('nivelEducacional', 40),
    'experienciasLaboralesPrevias' => cleanString('experienciasLaboralesPrevias', 1500),
    'comoSeEnteroDelTrabajo' => cleanString('comoSeEnteroDelTrabajo', 80),
];

$errors = [];
$requiredLabels = [
    'nombres' => 'Ingresa tu nombre.',
    'apellidos' => 'Ingresa tus apellidos.',
    'fechaDeNacimiento' => 'Ingresa tu fecha de nacimiento.',
    'nacionalidad' => 'Selecciona tu nacionalidad.',
    'email' => 'Ingresa tu email.',
    'celular' => 'Ingresa tu teléfono celular.',
    'direccionCalle' => 'Ingresa la calle de tu dirección.',
    'direccionNumero' => 'Ingresa el número de tu dirección.',
    'comuna' => 'Ingresa tu comuna.',
    'region' => 'Selecciona tu región.',
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

foreach ($requiredLabels as $field => $message) {
    if ($data[$field] === '') {
        $errors[$field] = $message;
    }
}

if ($data['nacionalidad'] !== '' && !allowedValue($data['nacionalidad'], ['Chilena', 'Extranjera'])) {
    $errors['nacionalidad'] = 'La nacionalidad seleccionada no es válida.';
}

if ($data['nacionalidad'] === 'Chilena') {
    if ($data['rut'] === '') {
        $errors['rut'] = 'Ingresa tu RUT.';
    } elseif (!validRut($data['rut'])) {
        $errors['rut'] = 'El RUT ingresado no es válido.';
    }
} elseif ($data['nacionalidad'] === 'Extranjera') {
    if ($data['rut'] !== '' && !validRut($data['rut'])) {
        $errors['rut'] = 'El RUT ingresado no es válido.';
    }
    if ($data['paisDeOrigen'] === '') {
        $errors['paisDeOrigen'] = 'Ingresa tu país de origen.';
    }
    if ($data['numeroDePasaporte'] === '') {
        $errors['numeroDePasaporte'] = 'Ingresa tu número de pasaporte o documento.';
    }
}

if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
    $errors['email'] = 'Ingresa un email válido.';
}
if ($data['celular'] !== '' && !validPhone($data['celular'])) {
    $errors['celular'] = 'Ingresa un teléfono válido.';
}

if ($data['fechaDeNacimiento'] !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['fechaDeNacimiento']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $invalidDate = $date === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0));
    if ($invalidDate || $date > new DateTimeImmutable('today') || $date < new DateTimeImmutable('1900-01-01')) {
        $errors['fechaDeNacimiento'] = 'Ingresa una fecha de nacimiento válida.';
    }
}

$enumChecks = [
    'region' => [$regions, 'La región seleccionada no es válida.'],
    'enQuePlantaDeseaTrabajar' => [$plants, 'La planta seleccionada no es válida.'],
    'temporadasTrabajadasEnDDC' => [$seasons, 'La cantidad de temporadas no es válida.'],
    'trabajoAlQuePostula' => [$jobs, 'El cargo seleccionado no es válido.'],
    'disponibilidadDeTurnos' => [$shifts, 'El turno seleccionado no es válido.'],
    'tallaDePantalon' => [$clothingSizes, 'La talla de pantalón no es válida.'],
    'tallaDePolera' => [$clothingSizes, 'La talla de polera no es válida.'],
    'numeroDeCalzado' => [$shoeSizes, 'El número de calzado no es válido.'],
    'nivelEducacional' => [$education, 'El nivel educacional no es válido.'],
    'comoSeEnteroDelTrabajo' => [$sources, 'La fuente seleccionada no es válida.'],
];
foreach ($enumChecks as $field => [$allowed, $message]) {
    if ($data[$field] !== '' && !allowedValue($data[$field], $allowed)) {
        $errors[$field] = $message;
    }
}

if (!isset($_POST['privacy_consent']) || $_POST['privacy_consent'] !== '1') {
    $errors['privacy_consent'] = 'Debes autorizar el tratamiento de tus datos para enviar la postulación.';
}

$cv = validateCv($errors);
if ($errors !== []) {
    respond(422, ['ok' => false, 'message' => 'Revisa los datos ingresados.', 'errors' => $errors]);
}

$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort = (int)(getenv('SMTP_PORT') ?: 465);
$smtpUser = getenv('SMTP_USER') ?: '';
$smtpPass = getenv('SMTP_PASS') ?: '';
$smtpSecure = strtolower(getenv('SMTP_SECURE') ?: 'ssl');
$fromAddress = getenv('SMTP_FROM') ?: $smtpUser;
$fromName = getenv('SMTP_FROM_NAME') ?: 'David Del Curto - Postulaciones';
$recipient = getenv('APPLICATION_RECIPIENT') ?: '';

if ($smtpUser === '' || $smtpPass === '' || $fromAddress === '' || $recipient === '') {
    error_log('Formulario DDC: faltan variables SMTP_USER, SMTP_PASS, SMTP_FROM o APPLICATION_RECIPIENT.');
    respond(500, ['ok' => false, 'message' => 'El servicio de postulaciones no está configurado correctamente.']);
}

$plainRows = [
    'Nombre(s)' => $data['nombres'],
    'Apellido(s)' => $data['apellidos'],
    'Fecha de nacimiento' => $data['fechaDeNacimiento'],
    'Nacionalidad' => $data['nacionalidad'],
    'RUT' => $data['rut'] ?: 'No informado',
    'País de origen' => $data['paisDeOrigen'] ?: 'No aplica',
    'Pasaporte/documento' => $data['numeroDePasaporte'] ?: 'No aplica',
    'Email' => $data['email'],
    'Celular' => $data['celular'],
    'Dirección' => trim($data['direccionCalle'] . ' ' . $data['direccionNumero']),
    'Villa / población' => $data['villaPoblacion'] ?: 'No informado',
    'Comuna' => $data['comuna'],
    'Región' => $data['region'],
    'Planta' => $data['enQuePlantaDeseaTrabajar'],
    'Temporadas en DDC' => $data['temporadasTrabajadasEnDDC'],
    'Cargo' => $data['trabajoAlQuePostula'],
    'Disponibilidad de turnos' => $data['disponibilidadDeTurnos'],
    'Talla de pantalón' => $data['tallaDePantalon'],
    'Talla de polera' => $data['tallaDePolera'],
    'Número de calzado' => $data['numeroDeCalzado'],
    'Nivel educacional' => $data['nivelEducacional'],
    'Experiencia laboral' => $data['experienciasLaboralesPrevias'],
    'Cómo se enteró' => $data['comoSeEnteroDelTrabajo'],
];

try {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->Port = $smtpPort;
    $mail->SMTPSecure = $smtpSecure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->setFrom($fromAddress, $fromName);
    $mail->addAddress($recipient);
    $mail->addReplyTo($data['email'], trim($data['nombres'] . ' ' . $data['apellidos']));
    $mail->isHTML(true);

    $subjectName = preg_replace('/[\r\n]+/', ' ', trim($data['nombres'] . ' ' . $data['apellidos'])) ?? 'Postulante';
    $mail->Subject = 'Postulación DDC - ' . $subjectName . ' - ' . $data['trabajoAlQuePostula'];
    $mail->Body = buildEmailBody($data);
    $mail->AltBody = "Nueva postulación DDC\n\n" . implode("\n", array_map(
        static fn($label, $value) => $label . ': ' . $value,
        array_keys($plainRows),
        array_values($plainRows)
    ));

    if ($cv !== null) {
        $mail->addAttachment($cv['tmp_name'], $cv['safe_name'], 'base64', $cv['mime']);
    }

    $mail->send();
} catch (Exception $e) {
    error_log('Formulario DDC: error PHPMailer: ' . $e->getMessage());
    respond(502, ['ok' => false, 'message' => 'No pudimos enviar tu postulación en este momento. Intenta nuevamente más tarde.']);
} catch (Throwable $e) {
    error_log('Formulario DDC: error inesperado: ' . $e->getMessage());
    respond(500, ['ok' => false, 'message' => 'Ocurrió un error inesperado al procesar la postulación.']);
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
respond(200, [
    'ok' => true,
    'message' => 'Tu postulación fue enviada correctamente. Gracias por tu interés en DDC.',
    'csrf_token' => $_SESSION['csrf_token'],
]);
