<?php

class Lib_GoogleApiGmail {
    var $curl;

    function args($args) {
        list(
            $this->curl,
        ) = $args;
    }

    function getToken($clientId, $clientSecret, $refreshToken) {
        return $this->curl->send('https://oauth2.googleapis.com/token', array(
            'method' => 'POST',
            'header' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'content' => http_build_query(array(
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            )),
        ));
    }

    function send($access_token, $from, $to, $subject, $body) {
        // Step 1: Create raw email message (RFC 2822)
        $rawMessage = "From: $from\r\n";
        $rawMessage .= "To: $to\r\n";
        $rawMessage .= "Subject: $subject\r\n";
        $rawMessage .= "\r\n";
        $rawMessage .= "$body";
        // Step 2: Base64 URL-safe encode
        $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');
        // Step 3: Send via Gmail API
        return $this->curl->send('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', array(
            'method' => 'POST',
            'header' => array(
                "Authorization" => 'Bearer ' . $access_token,
                "Content-Type" => 'application/json'
            ),
            'content' => json_encode(array(
                'raw' => $encodedMessage
            )),
        ));
    }
}