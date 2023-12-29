<?php
function validate(): bool {
    return isset($_POST["name"]) && strlen($_POST["name"]) > 0 && isset($_POST["email"])
        && strlen($_POST["email"]) > 0 && isset($_POST["message"]) && strlen($_POST["message"]) > 0
        && isset($_POST["token"]) && strlen($_POST["token"]) > 0;
}

function verifyRecaptcha($endpoint, $config): int {
    $data = "secret=" . $config["rc-key"] . "&response=" . $_POST["token"];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    $code = 0;

    if (curl_errno($ch) || curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 200) {
        $code = 1;
    } elseif (json_decode($response, true)["success"] === false) {
        $code = 2;
    }

    return $code;
}

function createCustomer($baseUrl, $token, $name, $email): bool {
    $data = json_encode(array(
        'displayName' => $name,
        'fullName' => $name,
        'email' => $email,
    ));

    $ch = curl_init($baseUrl . '/customer?strictConflictStatusCode=true');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if (!curl_errno($ch) && ($http_code === 201 || $http_code === 409)) {
        return true;
    }

    return false;
}

function createRequest($baseUrl, $token, $email, $message): bool {
    $summary = $message;
    if (strlen($summary) > 50) {
        $summary = substr($summary, 0, 47) . "...";
    }

    $data = json_encode(array(
        'isAdfRequest' => false,
        'requestFieldValues' => array(
            'summary' => $summary,
            'description' => $message,
        ),
        'raiseOnBehalfOf' => $email,
        'requestTypeId' => "10013",
        'serviceDeskId' => '1'
    ));

    $ch = curl_init($baseUrl . '/request');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . $token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
    ));
    curl_exec($ch);

    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 201) {
        return true;
    }

    return false;
}

$config = include('../../config.php');

$recaptchaEndpoint = "https://www.google.com/recaptcha/api/siteverify";                 // reCAPTCHA API

if (!validate()) {                                              // Check if request had all required info
    http_response_code(400);
    exit('We\'re missing some required information! Please fill out all fields and email <a href="mailto:help@sebsscholarship.org">help@sebsscholarship.org</a> directly if the issue persists.');
}

$recaptcha = verifyRecaptcha($recaptchaEndpoint, $config);      // Check if reCAPTCHA verification passed
if ($recaptcha === 1) {
    http_response_code(500);
    exit('There was an error verifying your request.');
} elseif ($recaptcha === 2) {
    http_response_code(401);
    exit('reCAPTCHA verification failed. Are you a robot?');
}

$baseUrl = 'https://sebsscholarship.atlassian.net/rest/servicedeskapi';
$token = base64_encode($config['jiraUser'] . ":" . $config['jiraApiKey']);

if (!createCustomer($baseUrl, $token, $_POST["name"], $_POST["email"])
    || !createRequest($baseUrl, $token, $_POST["email"], $_POST["message"])) {
    http_response_code(500);
    exit('There was an error submitting your message.');
}

exit('Message has been sent!');


