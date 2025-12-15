<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | You can obtain this file from the Firebase Console:
    | Project Settings -> Service Accounts -> Generate New Private Key
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | Your Firebase Realtime Database URL (optional)
    |
    */

    'database_url' => env('FIREBASE_DATABASE_URL'),
];
