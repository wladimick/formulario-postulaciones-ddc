<?php
/**
 * Plugin Name: DDC Postulaciones - Prueba
 * Description: Shortcode temporal para probar la ruta AJAX y wp_mail()/WP Mail SMTP sin completar el formulario de postulaciones.
 * Version: 1.0.0
 * Author: TIBOX
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ddc_postulaciones_test_ajax_url(): string
{
    $pluginUrl = plugin_dir_url(__FILE__);
    $marker = '/wp-content/';
    $position = strpos($pluginUrl, $marker);

    if ($position !== false) {
        return substr($pluginUrl, 0, $position) . '/wp-admin/admin-ajax.php';
    }

    return admin_url('admin-ajax.php');
}

add_shortcode('ddc_prueba_correo', static function (): string {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<p>Esta prueba solo está disponible para administradores conectados.</p>';
    }

    $recipient = sanitize_email((string) get_option('ddc_postulaciones_recipient', get_option('admin_email')));
    $ajaxUrl = ddc_postulaciones_test_ajax_url();
    $nonce = wp_create_nonce('ddc_postulaciones_test_mail');

    ob_start();
    ?>
    <div id="ddc-mail-test" style="max-width:620px;padding:24px;background:#f3f4f6;border:1px solid #d8dee8;font-family:Arial,sans-serif;">
        <h2 style="margin:0 0 12px;color:#003DA6;">Prueba rápida DDC</h2>
        <p style="margin:0 0 8px;">Esta prueba envía un correo simple mediante <code>wp_mail()</code>.</p>
        <p style="margin:0 0 8px;"><strong>Destinatario:</strong> <?php echo esc_html($recipient ?: 'No configurado'); ?></p>
        <p style="margin:0 0 18px;font-size:13px;color:#606060;"><strong>Endpoint:</strong> <code><?php echo esc_html($ajaxUrl); ?></code></p>

        <button id="ddc-mail-test-button" type="button" style="padding:11px 18px;border:0;background:#003DA6;color:#fff;font-weight:600;cursor:pointer;">Enviar correo de prueba</button>
        <pre id="ddc-mail-test-result" style="display:none;margin-top:16px;padding:14px;white-space:pre-wrap;overflow-wrap:anywhere;background:#fff;border:1px solid #d8dee8;font-size:12px;"></pre>
    </div>
    <script>
    (() => {
        const button = document.getElementById('ddc-mail-test-button');
        const result = document.getElementById('ddc-mail-test-result');
        if (!button || !result) return;

        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = 'Enviando…';
            result.style.display = 'block';
            result.textContent = 'Enviando solicitud…';

            const formData = new FormData();
            formData.append('action', 'ddc_test_mail');
            formData.append('nonce', <?php echo wp_json_encode($nonce); ?>);

            try {
                const response = await fetch(<?php echo wp_json_encode($ajaxUrl); ?>, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });

                const raw = await response.text();
                const contentType = response.headers.get('content-type') || 'sin content-type';
                let payload = null;

                try {
                    payload = JSON.parse(raw);
                } catch (_) {}

                if (!payload) {
                    result.textContent = `HTTP ${response.status} ${response.statusText}\nContent-Type: ${contentType}\n\nRespuesta:\n${raw.slice(0, 1200)}`;
                    console.error('DDC prueba correo · respuesta no JSON', { response, raw });
                    return;
                }

                result.textContent = `HTTP ${response.status}\n\n${JSON.stringify(payload, null, 2)}`;
                console.log('DDC prueba correo', payload);
            } catch (error) {
                result.textContent = `Error de red/JavaScript: ${error?.message || error}`;
                console.error('DDC prueba correo · error', error);
            } finally {
                button.disabled = false;
                button.textContent = 'Enviar correo de prueba';
            }
        });
    })();
    </script>
    <?php
    return (string) ob_get_clean();
});

add_action('wp_ajax_ddc_test_mail', static function (): void {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No autorizado.'], 403);
    }

    $nonce = isset($_POST['nonce']) && is_string($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ddc_postulaciones_test_mail')) {
        wp_send_json_error(['message' => 'Nonce inválido o expirado. Recarga la página.'], 403);
    }

    $recipient = sanitize_email((string) get_option('ddc_postulaciones_recipient', get_option('admin_email')));
    if (!is_email($recipient)) {
        wp_send_json_error([
            'message' => 'El destinatario de Postulaciones DDC no está configurado correctamente.',
            'recipient_configured' => false,
        ], 500);
    }

    $subject = 'Prueba técnica DDC Postulaciones - ' . wp_date('d/m/Y H:i:s');
    $body = '<div style="font-family:Arial,sans-serif;max-width:600px;padding:24px;border:1px solid #dce7f7;">'
        . '<h2 style="color:#003DA6;">Prueba de correo DDC</h2>'
        . '<p>Si recibiste este mensaje, el flujo <strong>WordPress → wp_mail() → WP Mail SMTP → proveedor de correo</strong> está funcionando.</p>'
        . '<p style="color:#606060;font-size:13px;">Fecha: ' . esc_html(wp_date('d/m/Y H:i:s')) . '</p>'
        . '</div>';

    $sent = wp_mail($recipient, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

    if (!$sent) {
        wp_send_json_error([
            'message' => 'wp_mail() devolvió false. Revisa WP Mail SMTP y el log de errores de WordPress.',
            'ajax_reached' => true,
            'recipient' => $recipient,
        ], 502);
    }

    wp_send_json_success([
        'message' => 'Correo de prueba enviado correctamente.',
        'ajax_reached' => true,
        'wp_mail' => true,
        'recipient' => $recipient,
    ]);
});
