<?php

class FirebaseAuthHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function verifyToken($idToken) {
        // Call the verifyFirebaseToken function from the provided code
        $verificationResult = verifyFirebaseToken($idToken);

        if ($verificationResult['success']) {
            // Token is valid, sync user in local database
            return $this->syncUserInDatabase($verificationResult);
        } else {
            // Invalid token
            return false;
        }
    }

    private function syncUserInDatabase($userData) {
        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE firebase_uid = ?");
            $stmt->execute([$userData['uid']]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                // Update existing user
                $updateStmt = $this->pdo->prepare("
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
                $insertStmt = $this->pdo->prepare("
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
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE firebase_uid = ?");
                $stmt->execute([$userData['uid']]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // Log error
            error_log('User Sync Error: ' . $e->getMessage());
            return false;
        }
    }
}