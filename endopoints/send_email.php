<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'atharvadankhade07@gmail.com';
    $mail->Password = 'zhcjjxeaxkalsfua';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->setFrom('atharvadankhade07@gmail.com');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Verification mail';
    $mail->Body = 'Hello';

    try {
        $mail->send();
        $response[] = ["message" => "Message sent to " . $email];
    } catch (Exception $e) {
        $response[] = ["message" => "Mailer Error: " . $mail->ErrorInfo];
    }
} else {
    $response[] = ["message" => "Invalid request method"];
}

echo json_encode($response);
