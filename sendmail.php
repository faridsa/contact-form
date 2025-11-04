<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once "vendor/autoload.php";

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Validar que las variables requeridas existan
$dotenv->required([
    "SMTP_HOST",
    "SMTP_USER",
    "SMTP_PASS",
    "DESTINATION_EMAIL",
    "FROM_EMAIL",
    "FROM_NAME",
    "TURNSTILE_SECRET",
]);

$isAjax =
    !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");
    $errors = [];

    // Validar Turnstile
    if (empty($_POST["cf-turnstile-response"])) {
        $errors[] = "Por favor, complete la verificación de seguridad.";
    }

    if (empty($errors)) {
        $turnstileToken = $_POST["cf-turnstile-response"];
        $response = file_get_contents(
            "https://challenges.cloudflare.com/turnstile/v0/siteverify?secret=" .
                $_ENV["TURNSTILE_SECRET"] .
                "&response={$turnstileToken}",
        );
        $responseKeys = json_decode($response, true);

        if (intval($responseKeys["success"]) !== 1) {
            $errors[] = "Error de verificación de seguridad. Por favor, inténtelo de nuevo.";
        }
    }

    // Validar tiempo de envío (honeypot de tiempo)
    if (empty($errors)) {
        $minSubmitTime = 10; // Segundos
        $loadTime = isset($_POST['token']) ? (int)$_POST['token'] : 0;
        $submitTime = time();

        if (($submitTime - $loadTime) < $minSubmitTime) {
            // Es un bot, responder con éxito pero no enviar correo.
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Su mensaje ha sido enviado, le responderemos a la brevedad."]);
            exit;
        }
    }

    // Validar y sanitizar entradas
    $name = filter_var(
        trim($_POST["name"] ?? ""),
        FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    );
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
    $phone = filter_var(
        trim($_POST["phone"] ?? ""),
        FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    );
    $mobile = filter_var(
        trim($_POST["mobile"] ?? ""),
        FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    );
    $message = filter_var(
        trim($_POST["message"] ?? ""),
        FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    );

    // Validaciones adicionales
    if (empty($name) || empty($email) || empty($message)) {
        $errors[] = "Por favor, complete todos los campos requeridos (Nombre, E-Mail y Consulta).";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Por favor, ingrese un email válido.";
    }

    // Longitudes máximas
    if (strlen($name) > 100) {
        $errors[] = "El nombre no puede exceder los 100 caracteres.";
    }
    if (strlen($email) > 100) {
        $errors[] = "El email no puede exceder los 100 caracteres.";
    }
    if (strlen($phone) > 20) {
        $errors[] = "El teléfono no puede exceder los 20 caracteres.";
    }
    if (strlen($mobile) > 20) {
        $errors[] = "El celular no puede exceder los 20 caracteres.";
    }
    if (strlen($message) > 2000) {
        $errors[] = "La consulta no puede exceder los 2000 caracteres.";
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $errors]);
        exit;
    }

    // Enviar correo
    $result = sendmail([
        "name" => $name, "email" => $email, "phone" => $phone,
        "mobile" => $mobile, "message" => $message,
    ]);

    echo json_encode($result);
}

function sendmail($data)
{
    $mail = new PHPMailer(false);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $_ENV["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV["SMTP_USER"];
        $mail->Password = $_ENV["SMTP_PASS"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $_ENV["SMTP_PORT"] ?? 465; // Valor por defecto

        $mail->setFrom($_ENV["FROM_EMAIL"], $_ENV["FROM_NAME"]);
        $mail->addAddress($_ENV["DESTINATION_EMAIL"]);
        $mail->addReplyTo(
            $mail->secureHeader($data["email"]),
            $mail->secureHeader($data["name"]),
        );

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $mail->secureHeader(
            $data["subject"] ?? "Consulta desde el sitio web",
        );
        $mail->Body = "<p>Se ha recibido un nuevo mensaje a traves del formulario de contacto, con los siguientes datos:</p>";
        $mail->Body .= "<p>Nombre: " . $data["name"] . "</p>";
        $mail->Body .= "<p>Email: " . $data["email"] . "</p>";
        $mail->Body .= "<p>Teléfono: " . $data["phone"] . "</p>";
        $mail->Body .= "<p>Celular: " . $data["mobile"] . "</p>";        
        $mail->Body .= "<p>Consulta: " . nl2br($data["message"]) . "</p>";
        $mail->AltBody = strip_tags($data["message"]); // Versión texto plano

        $mail->send();
        $response = [
            "status" => "success",
            "message" =>
                "Su mensaje ha sido enviado, le responderemos a la brevedad.",
        ];
        http_response_code(200);
        return $response;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $e->getMessage());
        http_response_code(400);
        $response = [
            "status" => "error",
            "message" =>
                "Ocurrió un error al enviar su mensaje. Por favor, escriba directamente a ".$_ENV["DESTINATION_EMAIL"],
        ];
        return $response;
    }
}
