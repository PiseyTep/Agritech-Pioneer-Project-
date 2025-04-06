<?php
// This script handles tractor creation

// Forward the request data
$apiUrl = "http://172.20.10.3:8000/api/admin/tractors";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

// Get token from request and forward it
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $headers['Authorization']
    ]);
}

// Forward the form data
curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);

// Forward any files
if (!empty($_FILES)) {
    $postFields = [];
    
    // Add regular form fields
    foreach ($_POST as $key => $value) {
        $postFields[$key] = $value;
    }
    
    // Add files
    foreach ($_FILES as $key => $file) {
        $postFields[$key] = new CURLFile(
            $file['tmp_name'],
            $file['type'],
            $file['name']
        );
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
}

// Execute cURL session
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode([
        'success' => false,
        'message' => 'cURL Error: ' . curl_error($ch)
    ]);
    exit;
}

// Close cURL session
curl_close($ch);

// Pass the API response directly back to the client
header('Content-Type: application/json');
echo $response;