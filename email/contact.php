<?php
// Salesforce REST API: https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/resources_list.htm
// Salesforce Cases API: https://developer.salesforce.com/docs/atlas.en-us.226.0.object_reference.meta/object_reference/sforce_api_objects_case.htm
// Salesforce JWT OAuth: https://help.salesforce.com/articleView?id=remoteaccess_oauth_jwt_flow.htm&type=5
// OAuth Authorization: https://login.salesforce.com/services/oauth2/authorize?response_type=token&client_id=3MVG9Kip4IKAZQEXRsS0YD5c1R6FtIVV6IrGlckdJRiGd.B0bIIxaFZ7m9BzSGlkpdTWKLeAz4fIkAlXM4bV7&redirect_uri=https://login.salesforce.com/services/oauth2/success

use \Firebase\JWT\JWT;

require('../vendor/firebase/php-jwt/src/JWT.php');

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

function getToken($endpoint, $config, $privateKey) {
    $payload = array(
        "iss" => $config['sfClientId'],
        "aud" => "https://login.salesforce.com",
        "sub" => $config['sfUser'],
        "exp" => strval(time() + (3 * 60))
    );

    $jwt = JWT::encode($payload, $privateKey, 'RS256');

    $data = http_build_query(array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ));

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($data)
    ));
    $response = curl_exec($ch);
    $token = null;

    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
        $token = json_decode($response, true);
    }

    curl_close($ch);
    return $token;
}

function createCase($endpoint, $token) {
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
            'Content-Length: ' . strlen($data)
    ));
    $response = curl_exec($ch);

    $id = null;
    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) == 201) {
        $id = json_decode($response, true)["id"];
    }

    curl_close($ch);
    return $id;
}

function notifyRecipient($endpoint, $token, $id) {
    $data = json_encode(array(
        'inputs' => array(
            array('SObjectRowId' => $id)
        )
    ));

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    curl_exec($ch);

    $status = false;
    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) == 200) {
        $status = true;
    }

    curl_close($ch);
    return $status;
}

$config = include('../../config.php');

$recaptchaEndpoint = "https://www.google.com/recaptcha/api/siteverify";                 // reCAPTCHA API
$oauthEndpoint = "https://login.salesforce.com/services/oauth2/token";                  // OAuth 2.0 Token API

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

$response = getToken($oauthEndpoint, $config, file_get_contents('../../private'));
if (is_null($response)) {                      // Check if application is OAuth authenticated
    http_response_code(500);
    exit('There was an error authenticating your request.');
}

$token = $response["access_token"];
$caseEndpoint = $response["instance_url"] . "/services/data/v48.0/sobjects/Case/"; // Authenticated Case API
$notifyEndpoint = $response["instance_url"] . "/services/data/v48.0/actions/custom/emailAlert/Case/Auto_Response/";

$id = createCase($caseEndpoint, $token);     // Submit the case to Salesforce
if (is_null($id)) {
    http_response_code(500);
    exit('There was an error submitting your message.');
}

if (!notifyRecipient($notifyEndpoint, $token, $id)) {
    http_response_code(500);
    exit('There was an error sending your confirmation message.');
}

exit('Message has been sent!');


