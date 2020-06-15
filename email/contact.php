<?php
// Freshdesk API: https://developers.freshdesk.com/api/

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

if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
    curl_close($ch);
    if (json_decode($response, true)["success"] === false) {
        http_response_code(401);
        exit('reCAPTCHA verification failed.');
    }
} else {
    curl_close($ch);
    http_response_code(500);
    exit('There was an error verifying your request.');
}

$config = include('../../config.php');  // Get config from on-server file
$urlBase = "https://sebsscholarship.freshdesk.com/api/v2/tickets"; // API endpoint for our org

$data = array(  // Build ticket payload
    'email' => $_POST["email"],
    'name' => $_POST["name"],
    'description' => $_POST["message"]
);
$jsonData = json_encode($data); // Convert to JSON string

$fdConn = curl_init($urlBase);  // The url to connect to
curl_setopt($mcConn, CURLOPT_USERPWD, $config["fd-key"] . ":X");    // Authentication
curl_setopt($mcConn, CURLOPT_HTTPHEADER, array(                     // Necessary HTTP header info
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData))
);
curl_setopt($ch, CURLOPT_POST, 1);                                  // POST
curl_setopt($fdConn, CURLOPT_POSTFIELDS, $jsonData);                // Attach POST payload
curl_setopt($fdConn, CURLOPT_RETURNTRANSFER, true);                 // Return response instead of printing

curl_exec($fdConn);

if (!curl_errno($fdConn) && curl_getinfo($fdConn, CURLINFO_RESPONSE_CODE) === 200) {
    curl_close($fdConn);
    echo 'Message has been sent!';
} else {
    curl_close($ch);
    http_response_code(400);
    echo 'There was an error sending your message. Please try again and email <a href="mailto:help@sebsscholarship.org">help@sebsscholarship.org</a> directly if the issue persists.';
}
