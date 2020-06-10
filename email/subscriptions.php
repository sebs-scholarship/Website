<?php
// Mailchimp Contact API reference: https://mailchimp.com/developer/guides/manage-subscribers-with-the-mailchimp-api/

// This is just a convenience class to make the code easier to read
abstract class STATUS {
    const MISSING = 0;  // The user is not a contact
    const SUBSCRIBED = 1;   // The user is a contact and is subscribed
    const NOT_SUBSCRIBED = 2;   // The user is a contact and is not subscribed
}

$config = include('../../config.php');  // Get config from on-server file
$urlBase = "https://us4.api.mailchimp.com/3.0/lists/" . $config["listID"] . "/members/"; // API endpoint for our list

// Function to help determine if a user is missing, subscribed, or not subscribed.
function userExists($userHash) {
    global $config, $urlBase;   // Use the global variables above

    $ch = curl_init($urlBase . $userHash);  // We use cURL to make HTTP requests
    curl_setopt($ch, CURLOPT_USERPWD, "user:" . $config["mc-key"]); // Our authentication
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); // Do an HTTP GET request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return our output rather than print it

    $response = curl_exec($ch); // Make the request and get the response

    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) { // If the response was a success
        curl_close($ch);    // Always remember to close your connections
        $status = json_decode($response, true)["status"];   // Parse text into JSON-like object and get status

        if ($status === "subscribed" || $status === "pending") {
            return STATUS::SUBSCRIBED;
        } else {
            return STATUS::NOT_SUBSCRIBED;
        }

    } else {    // If the response was a failure (May need to make an error page for curl errors)
        curl_close($ch);
        return STATUS::MISSING;
    }
}

// Updates the user's status to new status
function updateUserStatus($userHash, $status) {
    global $config, $urlBase;

    $data = array(  // This will be our payload to the server
        'status' => $status
    );
    $jsonData = json_encode($data); // Convert to a JSON string

    $ch = curl_init($urlBase . $userHash);
    curl_setopt($ch, CURLOPT_USERPWD, "user:" . $config["mc-key"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");   // Run a PATCH request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);    // Attach JSON payload
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( // Attach the JSON payload header info (HTTP requirement)
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData))
    );

    curl_exec($ch); // Submit request

    // Return based on success
    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
        curl_close($ch);
        return true;
    } else {
        curl_close($ch);
        return false;
    }
}

// Google reCAPTCHA validation documentation: https://developers.google.com/recaptcha/docs/verify
// Verify recaptcha token to prevent spam
// TODO: Could be made into a function
if (isset($_POST["g-recaptcha-response"]) && strlen($_POST["g-recaptcha-response"]) > 0) {
    $data = "secret=" . $config["rc-key"] . "&response=" . $_POST["g-recaptcha-response"];  // Build payload string

    $rcConn = curl_init("https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($rcConn, CURLOPT_POST, 1);  // Make a POST request
    curl_setopt($rcConn, CURLOPT_POSTFIELDS, $data);    // Attach payload
    curl_setopt($rcConn, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($rcConn);

    if (!curl_errno($rcConn) && curl_getinfo($rcConn, CURLINFO_RESPONSE_CODE) === 200) {
        curl_close($rcConn);
        if (json_decode($response, true)["success"] === false) { // If verification failed
            http_response_code(401);
            exit('reCAPTCHA verification failed.');
        }
    } else {    // If something went wrong
        curl_close($rcConn);
        http_response_code(500);
        exit('There was an error verifying your request.');
    }
} else {    // If response was missing recaptcha info
    http_response_code(400);
    exit("<h1>Bad Request</h1>");
}

// Handle subscription request
if (isset($_POST["sub"]) && isset($_POST["name"]) && strlen($_POST["name"]) > 0 && isset($_POST["email"])
    && strlen($_POST["email"]) > 0) {
    $userHash = md5($_POST["email"]);   // User ID for mailchimp
    $status = userExists($userHash);    // Check if user already exists or not

    if ($status === STATUS::MISSING) {  // If user does not exist
        // TODO: Could be made into a function
        $data = array(  // Build subscription payload
            'email_address' => $_POST["email"],
            'status' => 'pending',  // Send them an email to confirm
            'merge_fields' => array('NAME' => $_POST["name"])
        );

        $jsonData = json_encode($data);

        $mcConn = curl_init($urlBase);
        curl_setopt($mcConn, CURLOPT_USERPWD, "user:" . $config["key"]);
        curl_setopt($mcConn, CURLOPT_CUSTOMREQUEST, "POST");    // POST request
        curl_setopt($mcConn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($mcConn, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($mcConn, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData))
        );

        curl_exec($mcConn);

        if (!curl_errno($mcConn) && curl_getinfo($mcConn, CURLINFO_RESPONSE_CODE) === 200) {
            echo '<META HTTP-EQUIV="refresh" content="0;URL=confirm.html">';    // Tell them to check their email
        } else {
            echo '<META HTTP-EQUIV="refresh" content="0;URL=invalid.html">';    // Probably invalid email address
        }

        curl_close($mcConn);
    } else if ($status === STATUS::NOT_SUBSCRIBED) {
        updateUserStatus($userHash, "pending"); // Update their status to pending
        echo '<META HTTP-EQUIV="refresh" content="0;URL=confirm.html">';    // Check email
    } else {
        echo '<META HTTP-EQUIV="refresh" content="0;URL=subscribed.html">'; // They must already be subscribed
    }

// Handle unsubscriptions
} else if (isset($_POST["unsub"]) && isset($_POST["email"]) && strlen($_POST["email"]) > 0) {
    // TODO: Own function?
    $userHash = md5($_POST["email"]);
    $status = userExists($userHash);

    if ($status === STATUS::SUBSCRIBED && updateUserStatus($userHash, "unsubscribed")) {
        echo '<META HTTP-EQUIV="refresh" content="0;URL=unsubscribed.html">';   // Success!
    } else {
        echo '<META HTTP-EQUIV="refresh" content="0;URL=not-subscribed.html">'; // Never subscribed
    }

// The request was missing something important
} else {
    http_response_code(400);
    exit("<h1>Bad Request</h1>");
}