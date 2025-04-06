<?php
function callApi($method, $url, $data = null) {
    session_start();
    $curl = curl_init('http://172.20.10.3:8000/api/admin/' . $url);

    $headers = [
        'Authorization: Bearer ' . $_SESSION['api_token'],
        'Content-Type: application/json'
    ];

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($data) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}
