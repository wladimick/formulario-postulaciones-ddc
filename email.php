<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';

$secretKey = "secret";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se toma Json Web Token desde el header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
    } else {
        http_response_code(401);
        echo "Error";
        exit;
    }
    
    if (isset($_FILES['curriculum']) && $_FILES['curriculum']['error'] === UPLOAD_ERR_OK) {
        //Se guarda archivo temporalmente en carpeta uploads. Este archivo se borrará al finalizar el proceso
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['curriculum']['name']);
        
        // Se asegura que el directorio existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Se sube archivo
        if (move_uploaded_file($_FILES['curriculum']['tmp_name'], $uploadFile)) {
            // echo "File uploaded successfully to: " . $uploadFile;
        } else {
            echo "No se pudo subir el archivo. ";
        }
    }

    try {
        // Se decodifica JWT
        $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
    
        // CSe convierte en arreglo
        $decodedArray = (array)$decoded;
    } catch (\Firebase\JWT\ExpiredException $e) {
        http_response_code(401);
    } catch (\Exception $e) {
        http_response_code(400);
    }
    
    //Se crea instancia de PHPMailer
    $mail = new PHPMailer(true);
    $mail->CharSet = "UTF-8";
    
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'tiboxdesarrolloprueba@gmail.com'; //Dirección desde la cual se envía el correo.
    $mail->Password   = 'ikle aast vvjz hrib';             //Contraseña del correo (se usa una contraseña de aplicación)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    
    //Remitente
    $mail->setFrom('tiboxdesarrolloprueba@gmail.com', 'Tibox Desarrollo Prueba');
    
    //Destinatarios
    // $mail->addAddress('larenas@tibox.cl');
    // $mail->addAddress('jabello@tibox.cl');
    $mail->addAddress('gerenciapersonas@ddc.cl');
    
    //Mensaje
    $mensaje = '';
    $mensaje .= '<b>Nombre (s) :</b><br>' . $decodedArray["nombres"] . '<br><hr/>';
    $mensaje .= '<b>Apellido (s): </b><br>' . $decodedArray["apellidos"] . '<br><hr/>';
    $mensaje .= '<b>Fecha de Nacimiento: </b><br>' . $decodedArray["fechaDeNacimiento"] . '<br><hr/>';
    $mensaje .= '<b>Género: </b><br>' . $decodedArray["genero"] . '<br><hr/>';
    $mensaje .= '<b>Estado Civil: </b><br>' . $decodedArray["estadoCivil"] . '<br><hr/>';
    $mensaje .= '<b>Nacionalidad: </b><br>' . $decodedArray["nacionalidad"] . '<br><hr/>';
    $mensaje .= '<b>País de Origen: </b><br>' . $decodedArray["paisDeOrigen"] . '<br><hr/>';
    $mensaje .= '<b>Número de Pasaporte: </b><br>' . $decodedArray["numeroDePasaporte"] . '<br><hr/>';
    $mensaje .= '<b>Dirección Calle: </b><br>' . $decodedArray["direccionCalle"] . '<br><hr/>';
    $mensaje .= '<b>Dirección Número: </b><br>' . $decodedArray["direccionNumero"] . '<br><hr/>';
    $mensaje .= '<b>Villa Población: </b><br>' . $decodedArray["villaPoblacion"] . '<br><hr/>';
    $mensaje .= '<b>Comuna: </b><br>' . $decodedArray["comuna"] . '<br><hr/>';
    $mensaje .= '<b>Región: </b><br>' . $decodedArray["region"] . '<br><hr/>';
    $mensaje .= '<b>Celular: </b><br>' . $decodedArray["celular"] . '<br><hr/>';
    $mensaje .= '<b>Email: </b><br>' . $decodedArray["email"] . '<br><hr/>';
    $mensaje .= '<b>Contacto de Emergencia: </b><br>' . $decodedArray["contactoDeEmergencia"] . '<br><hr/>';
    $mensaje .= '<b>Teléfono Contacto de Emergencia: </b><br>' . $decodedArray["telefonoContactoDeEmergencia"] . '<br><hr/>';
    $mensaje .= '<b>Diseño Calle: </b><br>' . $decodedArray["disenoCalle"] . '<br><hr/>';
    $mensaje .= '<b>En qué planta desea trabajar: </b><br>' . $decodedArray["enQuePlantaDeseaTrabajar"] . '<br><hr/>';
    $mensaje .= '<b>Temporadas trabajadas en DDC: </b><br>' . $decodedArray["temporadasTrabajadasEnDDC"] . '<br><hr/>';
    $mensaje .= '<b>Trabajo al que postula: </b><br>' . $decodedArray["trabajoAlQuePostula"] . '<br><hr/>';
    $mensaje .= '<b>Disponibilidad de Turnos: </b><br>' . $decodedArray["disponibilidadDeTurnos"] . '<br><hr/>';
    $mensaje .= '<b>Talla de Pantalón: </b><br>' . $decodedArray["tallaDePantalon"] . '<br><hr/>';
    $mensaje .= '<b>Talla de Polera: </b><br>' . $decodedArray["tallaDePolera"] . '<br><hr/>';
    $mensaje .= '<b>Número de Calzado: </b><br>' . $decodedArray["numeroDeCalzado"] . '<br><hr/>';
    $mensaje .= '<b>Nivel Educacional: </b><br>' . $decodedArray["nivelEducacional"] . '<br><hr/>';
    $mensaje .= '<b>Experiencias Laborales Previas: </b><br>' . $decodedArray["experienciasLaboralesPrevias"] . '<br><hr/>';
    $mensaje .= '<b>Cómo se enteró del trabajo: </b><br>' . $decodedArray["comoSeEnteroDelTrabajo"] . '<br><hr/>';
    
    $mail->isHTML(true);
    $mail->Subject = 'Formulario View Our Jobs - ' . $decodedArray["nombres"]  . ' ' . $decodedArray["apellidos"] . ' - ' . $decodedArray["email"]; //Asunto
    $mail->Body    = $mensaje;

    //Adjunto
    $mail->addAttachment("uploads/" . basename($_FILES['curriculum']['name']));

    $mail->send(); //Se envía el correo
    echo "Correo Enviado";

    //Se borra el archivo
    unlink('uploads/' . basename($_FILES['curriculum']['name']));
}
else {
    echo "Error al intentar enviar formulario.";
}

?>