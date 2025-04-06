<?php
function verifyFirebaseToken($idToken) {
    // Your Firebase Web API Key (found in Firebase Console > Project Settings)
    $apiKey = 'AIzaSyBBHzS_G8j_LzDlU9Oig53XnoOBrKGspZ4';

    $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}";
    

    try {
        // Prepare cURL request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'idToken' => $idToken
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL connection
        curl_close($ch);

        // Check response
        if ($httpCode === 200) {
            $userData = json_decode($response, true);
            
            // Extract and return relevant user information
            return [
                'success' => true,
                'uid' => $userData['users'][0]['localId'],
                'email' => $userData['users'][0]['email'],
                'name' => $userData['users'][0]['displayName'] ?? null,
                'verified' => $userData['users'][0]['emailVerified'] ?? false
            ];
        } else {
            // Token is invalid or expired
            return [
                'success' => false,
                'message' => 'Invalid or expired token'
            ];
        }
    } catch (Exception $e) {
        // Handle any exceptions
        return [
            'success' => false,
            'message' => 'Token verification failed: ' . $e->getMessage()
        ];
    }
}

// Example usage in an API endpoint
function handleTokenVerification() {
    // Get token from Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    // Remove 'Bearer ' prefix
    $idToken = str_replace('Bearer ', '', $authHeader);

    if (empty($idToken)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'No token provided'
        ]);
        exit;
    }

    // Verify token
    $verificationResult = verifyFirebaseToken($idToken);

    if ($verificationResult['success']) {
        // Token is valid, proceed with your logic
        // Example: Sync user in local database
        $user = syncUserInDatabase($verificationResult);
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        // Invalid token
        http_response_code(401);
        echo json_encode($verificationResult);
    }
}

// Helper function to sync user in local database
function syncUserInDatabase($userData) {
    global $pdo; // Assuming $pdo is your database connection

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE firebase_uid = ?");
        $stmt->execute([$userData['uid']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // Update existing user
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET email = ?, name = ? 
                WHERE firebase_uid = ?
            ");
            $updateStmt->execute([
                $userData['email'], 
                $userData['name'], 
                $userData['uid']
            ]);
            return $existingUser;
        } else {
            // Create new user
            $insertStmt = $pdo->prepare("
                INSERT INTO users 
                (firebase_uid, email, name, role, status) 
                VALUES (?, ?, ?, 'farmer', 'active')
            ");
            $insertStmt->execute([
                $userData['uid'],
                $userData['email'],
                $userData['name']
            ]);

            // Fetch and return the newly created user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE firebase_uid = ?");
            $stmt->execute([$userData['uid']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Log error
        error_log('User Sync Error: ' . $e->getMessage());
        return null;
    }
}

// Call the handler if this is a token verification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['verify_token'])) {
    handleTokenVerification();
}