<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Auth;
use Exception;

class FirebaseService
{
    protected $messaging;
    protected $auth;
    protected $firestore;
    protected $database;
    protected $storage;

    public function __construct()
    {
        try {
            // Path to Firebase service account credentials
            $credentialsPath = storage_path('agritech-22-firebase-adminsdk-fbsvc-a3fc4710ea.json');
            
            $factory = (new Factory)->withServiceAccount($credentialsPath);

            // Initialize Firebase services
            $this->messaging = $factory->createMessaging();
            $this->auth = $factory->createAuth();
            $this->firestore = $factory->createFirestore();
            $this->database = $factory->createDatabase();
            $this->storage = $factory->createStorage();
        } catch (Exception $e) {
            // Log the error
            error_log('Firebase Initialization Error: ' . $e->getMessage());
            throw new Exception('Failed to initialize Firebase services: ' . $e->getMessage());
        }
    }

    /**
     * Send push notification to a specific device
     */
    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body));

            // Add additional data if provided
            if (!empty($data)) {
                $message = $message->withData($data);
            }

            return $this->messaging->send($message);
        } catch (Exception $e) {
            error_log('Notification Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify Firebase ID Token
     */
    public function verifyIdToken($idToken)
    {
        try {
            $verifiedToken = $this->auth->verifyIdToken($idToken);
            return $verifiedToken->getClaims();
        } catch (Exception $e) {
            error_log('Token Verification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new user in Firebase Authentication
     */
    public function createUser($userData)
    {
        try {
            $userProperties = [
                'email' => $userData['email'],
                'emailVerified' => false,
                'password' => $userData['password'],
                'displayName' => $userData['name']
            ];

            $createdUser = $this->auth->createUser($userProperties);
            return $createdUser;
        } catch (Exception $e) {
            error_log('Firebase User Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing user in Firebase Authentication
     */
    public function updateUser($uid, $userData)
    {
        try {
            $updateProperties = [];

            if (isset($userData['name'])) {
                $updateProperties['displayName'] = $userData['name'];
            }

            if (isset($userData['email'])) {
                $updateProperties['email'] = $userData['email'];
            }

            if (!empty($updateProperties)) {
                $updatedUser = $this->auth->updateUser($uid, $updateProperties);
                return $updatedUser;
            }

            return null;
        } catch (Exception $e) {
            error_log('Firebase User Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a user from Firebase Authentication
     */
    public function deleteUser($uid)
    {
        try {
            $this->auth->deleteUser($uid);
            return true;
        } catch (Exception $e) {
            error_log('Firebase User Deletion Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Store data in Firestore
     */
    public function storeInFirestore($collection, $documentId, $data)
    {
        try {
            $docRef = $this->firestore->collection($collection)->document($documentId);
            return $docRef->set($data);
        } catch (Exception $e) {
            error_log('Firestore Storage Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve data from Firestore
     */
    public function getFromFirestore($collection, $documentId)
    {
        try {
            $docRef = $this->firestore->collection($collection)->document($documentId);
            $snapshot = $docRef->snapshot();
            return $snapshot->exists() ? $snapshot->data() : null;
        } catch (Exception $e) {
            error_log('Firestore Retrieval Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload file to Firebase Storage
     */
    public function uploadFile($localFilePath, $storagePath)
    {
        try {
            $file = fopen($localFilePath, 'r');
            $bucket = $this->storage->getBucket();
            $object = $bucket->upload($file, [
                'name' => $storagePath
            ]);
            return $object->gcsUri();
        } catch (Exception $e) {
            error_log('Firebase Storage Upload Error: ' . $e->getMessage());
            return false;
        }
    }
}