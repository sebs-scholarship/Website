<?php
// Salesforce REST API: https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/resources_list.htm
// Salesforce Cases API: https://developer.salesforce.com/docs/atlas.en-us.226.0.object_reference.meta/object_reference/sforce_api_objects_case.htm
// Salesforce JWT OAuth: https://help.salesforce.com/articleView?id=remoteaccess_oauth_jwt_flow.htm&type=5
// Firebase JWT: https://github.com/firebase/php-jwt

use \Firebase\JWT\JWT;

require('../../php-jwt/src/JWT.php');

function validate() {
    return isset($_POST["name"]) && strlen($_POST["name"]) > 0 && isset($_POST["email"])
        && strlen($_POST["email"]) > 0 && isset($_POST["message"]) && strlen($_POST["message"]) > 0
        && isset($_POST["token"]) && strlen($_POST["token"]) > 0;
}

function verifyRecaptcha($endpoint, $config) {
    $data = "secret=" . $config["rc-key"] . "&response=" . $_POST["token"];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $code = 0;

    if (curl_errno($ch) || curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 200) {
        $code = 1;
    } elseif (json_decode($response, true)["success"] === false) {
        $code = 2;
    }

    curl_close($ch);
    return $code;
}

function getToken($endpoint, $config) {
    $privateKey = file_get_contents('../../private');

    $payload = array(
        "iss" => $config['sfClientId'],
        "aud" => "https://login.salesforce.com",
        "sub" => $config['sfUser'],
        "exp" => strval(time() + (3 * 60))
    );

    $jwt = JWT::encode($payload, $privateKey, 'RS256');

    $data = array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    );

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
    ));
    $response = curl_exec($ch);
    $token = null;

    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
        $token = json_decode($response, true)["access_token"];
    } else {
        http_response_code(500);
        exit("message: " . $jwt);
    }

    curl_close($ch);
    return $token;
}

function createCase($token, $endpoint) {
    $data = json_encode(array(
        'SuppliedName' => $_POST["name"],
        'SuppliedEmail' => $_POST["email"],
        'Subject' => "Contact Form Submission",
        'Description' => $_POST["message"],
        'Origin' => 'Contact Form'
    ));

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );
    curl_exec($ch);

    $status = true;
    if (curl_errno($ch) || curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 201) {
        $status = false;
    }

    curl_close($ch);
    return $status;
}

$config = include('../../config.php');

$recaptchaEndpoint = "https://www.google.com/recaptcha/api/siteverify";                     // reCAPTCHA API
$oauthEndpoint = "https://login.salesforce.com/services/oauth2/token";                      // OAuth 2.0 Token API
$caseEndpoint = "https://sebsscholarship.salesforce.com/services/data/v49.0/sobjects/Case"; // Org Case API

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

$token = getToken($oauthEndpoint, $config);                     // Check if application is OAuth authenticated
if (is_null($token)) {
    http_response_code(500);
    exit('There was an error authenticating your request.');
}

if (createCase($token, $caseEndpoint)) {                        // Submit the case to Salesforce
    exit('Message has been sent!');
} else {
    http_response_code(400);
    exit('There was an error sending your message. Please try again and email <a href="mailto:help@sebsscholarship.org">help@sebsscholarship.org</a> directly if the issue persists.');
}
