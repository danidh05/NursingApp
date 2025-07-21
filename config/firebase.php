<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [
        'app' => [
            'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/firebase-credentials.json')),
            
            'storage' => [
                'default_bucket' => env('FIREBASE_STORAGE_BUCKET', 'alahmadnursecare-f93ff.firebasestorage.app'),
            ],
        ],
    ],
]; 