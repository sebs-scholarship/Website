<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

abstract class STATUS {
    const SUBSCRIBED = "subscribed";
    const NOT_SUBSCRIBED = "unsubscribed";
    const MISSING = "missing";
}

$config = include('../../config.php');
$urlBase = "https://us4.api.mailchimp.com/3.0/lists/" . $config["listID"] . "/members/";

function userExists($userHash) {
    global $config, $urlBase;

    $ch = curl_init($urlBase . $userHash);
    curl_setopt($ch, CURLOPT_USERPWD, "user:" . $config["mc-key"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
        curl_close($ch);
        $status = json_decode($response, true)["status"];
        if ($status === "subscribed" || $status === "pending") {
            return STATUS::SUBSCRIBED;
        } else {
            return STATUS::NOT_SUBSCRIBED;
        }
    } else {
        curl_close($ch);
        return STATUS::MISSING;
    }
}

function updateUserStatus($userHash, $status) {
    global $config, $urlBase;

    $data = array(
        'status' => $status
    );
    $jsonData = json_encode($data);

    $ch = curl_init($urlBase . $userHash);
    curl_setopt($ch, CURLOPT_USERPWD, "user:" . $config["mc-key"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData))
    );

    curl_exec($ch);

    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
        curl_close($ch);
        return true;
    } else {
        curl_close($ch);
        return false;
    }
}

if (isset($_POST["g-recaptcha-response"]) && strlen($_POST["g-recaptcha-response"]) > 0) {
    $data = "secret=" . $config["rc-key"] . "&response=" . $_POST["g-recaptcha-response"];

    $rcConn = curl_init("https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($rcConn, CURLOPT_POST, 1);
    curl_setopt($rcConn, CURLOPT_POSTFIELDS, $data);
    curl_setopt($rcConn, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($rcConn);

    if (!curl_errno($rcConn) && curl_getinfo($rcConn, CURLINFO_RESPONSE_CODE) === 200) {
        curl_close($rcConn);
        if (json_decode($response, true)["success"] === false) {
            http_response_code(401);
            exit('reCAPTCHA verification failed.');
        }
    } else {
        curl_close($rcConn);
        http_response_code(500);
        exit('There was an error verifying your request.');
    }
} else {
    http_response_code(400);
    exit("<h1>Bad Request</h1>");
}

if (isset($_POST["sub"]) && isset($_POST["name"]) && strlen($_POST["name"]) > 0 && isset($_POST["email"])
    && strlen($_POST["email"]) > 0) {
    $userHash = md5($_POST["email"]);
    $status = userExists($userHash);
    if ($status === STATUS::MISSING) {
        $data = array(
            'email_address' => $_POST["email"],
            'status' => 'pending',
            'merge_fields' => array('NAME' => $_POST["name"])
        );

        $jsonData = json_encode($data);

        $mcConn = curl_init($urlBase);
        curl_setopt($mcConn, CURLOPT_USERPWD, "user:" . $config["key"]);
        curl_setopt($mcConn, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($mcConn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($mcConn, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($mcConn, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData))
        );

        curl_exec($mcConn);

        if (!curl_errno($mcConn) && curl_getinfo($mcConn, CURLINFO_RESPONSE_CODE) === 200) {
            echo '<META HTTP-EQUIV="refresh" content="0;URL=confirm.html">';
        } else {
            echo '<META HTTP-EQUIV="refresh" content="0;URL=invalid.html">';
        }

        curl_close($mcConn);
    } else if ($status === STATUS::NOT_SUBSCRIBED) {
        updateUserStatus($userHash, "pending");
        echo '<META HTTP-EQUIV="refresh" content="0;URL=confirm.html">';
    } else {
        echo '<META HTTP-EQUIV="refresh" content="0;URL=subscribed.html">';
    }
} else if (isset($_POST["unsub"]) && isset($_POST["email"]) && strlen($_POST["email"]) > 0) {
    $userHash = md5($_POST["email"]);
    $status = userExists($userHash);

    if ($status === STATUS::SUBSCRIBED && updateUserStatus($userHash, "unsubscribed")) {
        echo '<META HTTP-EQUIV="refresh" content="0;URL=unsubscribed.html">';
    } else {
        echo '<META HTTP-EQUIV="refresh" content="0;URL=not-subscribed.html">';
    }
} else {
    http_response_code(400);
    exit("<h1>Bad Request</h1>");
}