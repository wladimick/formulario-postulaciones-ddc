<?php
if (!defined('ABSPATH')) {
    exit;
}

function ddc_postulaciones_email_rows(array $rows): string
{
    $html = '';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="padding:12px 14px;border-bottom:1px solid #E8EEF7;width:38%;vertical-align:top;color:#606060;font-size:13px;font-weight:600;line-height:1.4;">' . esc_html((string) $label) . '</td>'
            . '<td style="padding:12px 14px;border-bottom:1px solid #E8EEF7;vertical-align:top;color:#2C2E65;font-size:14px;font-weight:400;line-height:1.5;word-break:break-word;">' . nl2br(esc_html((string) $value)) . '</td>'
            . '</tr>';
    }
    return $html;
}

function ddc_postulaciones_email_section(string $title, array $rows): string
{
    return '<tr><td style="padding:0 24px 22px 24px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;background:#FFFFFF;border:1px solid #DCE7F7;border-radius:10px;overflow:hidden;">'
        . '<tr><td colspan="2" style="padding:13px 16px;background:#F2F7FF;border-bottom:3px solid #3CB4E5;color:#003DA6;font-size:16px;font-weight:600;line-height:1.3;">' . esc_html($title) . '</td></tr>'
        . ddc_postulaciones_email_rows($rows)
        . '</table></td></tr>';
}

function ddc_postulaciones_build_email_body(array $data): string
{
    $fullName = trim($data['nombres'] . ' ' . $data['apellidos']);
    $dateLabel = $data['fechaDeNacimiento'];
    $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['fechaDeNacimiento']);
    if ($birthDate instanceof DateTimeImmutable) {
        $dateLabel = $birthDate->format('d/m/Y');
    }

    $personal = [
        'Nombre completo' => $fullName,
        'RUT' => $data['rut'] ?: 'No informado',
        'Fecha de nacimiento' => $dateLabel,
        'Género' => $data['genero'],
        'Estado civil' => $data['estadoCivil'],
        'Nacionalidad' => $data['nacionalidad'],
    ];
    if ($data['nacionalidad'] === 'Extranjera') {
        $personal['País de origen'] = $data['paisDeOrigen'];
        $personal['Pasaporte / documento'] = $data['numeroDePasaporte'];
    }

    $contact = [
        'Dirección' => trim($data['direccionCalle'] . ' ' . $data['direccionNumero']),
        'Villa / población' => $data['villaPoblacion'] ?: 'No informado',
        'Comuna' => $data['comuna'],
        'Región' => $data['region'],
        'Celular' => $data['celular'],
        'Email' => $data['email'],
        'Contacto de emergencia' => $data['contactoDeEmergencia'] ?: 'No informado',
        'Teléfono contacto de emergencia' => $data['telefonoContactoDeEmergencia'],
        'Diseño Calle' => $data['disenoCalle'] ?: 'No informado',
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

    return '<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nueva postulación DDC</title></head>'
        . '<body style="margin:0;padding:0;background:#F4F6F8;font-family:\'Titillium Web\',Arial,Helvetica,sans-serif;color:#606060;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#F4F6F8;border-collapse:collapse;"><tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:680px;border-collapse:separate;border-spacing:0;background:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 8px 28px rgba(44,46,101,.10);">'
        . '<tr><td style="padding:0;background:#003DA6;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td style="padding:24px 28px 20px;vertical-align:middle;"><div style="font-size:34px;line-height:1;color:#FFFFFF;font-weight:600;letter-spacing:-1px;">DDC<span style="color:#3CB4E5;">.</span></div><div style="padding-top:5px;color:#BFD6F7;font-size:12px;letter-spacing:.7px;text-transform:uppercase;">David Del Curto · Personas</div></td>'
        . '<td align="right" style="padding:24px 28px 20px 12px;vertical-align:middle;color:#FFFFFF;font-size:12px;font-weight:600;">NUEVA POSTULACIÓN</td>'
        . '</tr><tr><td colspan="2" style="height:5px;background:#00C7B1;font-size:0;line-height:0;">&nbsp;</td></tr></table></td></tr>'
        . '<tr><td style="padding:30px 28px 12px;"><div style="color:#606060;font-size:13px;font-weight:500;text-transform:uppercase;letter-spacing:.8px;">Postulación recibida</div><div style="padding-top:4px;color:#003DA6;font-size:27px;line-height:1.2;font-weight:600;">' . esc_html($fullName) . '</div><div style="padding-top:8px;color:#2C2E65;font-size:16px;line-height:1.45;">Postula a <strong>' . esc_html($data['trabajoAlQuePostula']) . '</strong> en la planta <strong>' . esc_html($data['enQuePlantaDeseaTrabajar']) . '</strong>.</div></td></tr>'
        . '<tr><td style="padding:8px 28px 26px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF6FF;border-radius:10px;border-left:4px solid #0662FF;"><tr><td style="padding:14px 16px;color:#2C2E65;font-size:13px;line-height:1.5;"><strong style="color:#003DA6;">Currículum adjunto.</strong> Puedes responder directamente a este correo para contactar a ' . esc_html($data['nombres']) . '.</td></tr></table></td></tr>'
        . ddc_postulaciones_email_section('Información personal', $personal)
        . ddc_postulaciones_email_section('Contacto y ubicación', $contact)
        . ddc_postulaciones_email_section('Postulación', $application)
        . ddc_postulaciones_email_section('Formación y experiencia', $profile)
        . ddc_postulaciones_email_section('Ropa de trabajo', $equipment)
        . '<tr><td style="padding:0 28px 28px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFD;border-radius:8px;"><tr><td style="padding:13px 15px;color:#606060;font-size:11px;line-height:1.5;">La persona postulante declaró autorizar el tratamiento de estos datos para gestionar su postulación laboral. Este correo puede contener información personal; úsala únicamente para fines del proceso de selección.</td></tr></table></td></tr>'
        . '<tr><td style="padding:20px 28px;background:#2C2E65;text-align:center;"><div style="color:#FFFFFF;font-size:13px;font-weight:600;">David Del Curto S.A.</div><div style="padding-top:4px;color:#BFD6F7;font-size:11px;line-height:1.5;">Postulaciones · Entregar buenos frutos</div></td></tr>'
        . '</table></td></tr></table></body></html>';
}
