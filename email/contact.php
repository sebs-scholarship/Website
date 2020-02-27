<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

$config = include('../../config.php');

if (!isset($_POST["name"]) || strlen($_POST["name"]) == 0 || !isset($_POST["email"]) || strlen($_POST["email"]) == 0
    || !isset($_POST["message"]) || strlen($_POST["message"]) == 0 || !isset($_POST["token"]) || strlen($_POST["token"]) == 0) {
    http_response_code(400);
    exit('There was an error sending your message. Please try again and email <a href="mailto:help@sebsscholarship.org">help@sebsscholarship.org</a> directly if the issue persists.');
}

$data = "secret=" . $config["rc-key"] . "&response=" . $_POST["token"];

$ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
    if (json_decode($response, true)["success"] === false) {
        http_response_code(401);
        exit('reCAPTCHA verification failed.');
    }
} else {
    http_response_code(500);
    exit('There was an error verifying your request.');
}

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.dreamhost.com';                   // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'contact@sebsscholarship.org';      // SMTP username
    $mail->Password = $config["smtpPassword"];            // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('contact@sebsscholarship.org', 'SSF Contact Form');  // Send from contact email address
    $mail->addAddress('help@sebsscholarship.org');                      // Send to help list
    $mail->addReplyTo($_POST["email"], $_POST["name"]);                 // Set reply-to to submitter's name
    $mail->addCC($_POST["email"]);                                      // Send the submitter a copy

    //Content
    $mail->isHTML(false);                                               // Make sure plain-text is on
    $mail->Subject = 'SEBS Scholarship Contact Form Submission';
    $mail->Body = $_POST["message"];                                    // Message from form

    $mail->send();
    echo 'Message has been sent!';
} catch (Exception $e) {
    http_response_code(400);
    echo 'There was an error sending your message. Please try again and email <a href="mailto:help@sebsscholarship.org">help@sebsscholarship.org</a> directly if the issue persists.';
}
