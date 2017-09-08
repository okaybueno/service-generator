<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Services namespace
    |--------------------------------------------------------------------------
    |
    | Base namespace for the services created.
    |
    */
    'groups' => [
        'MyApp\Services\Frontend' => app_path('MyApp/Services/Frontend'),
        'MyApp\Services\Backend' => app_path('MyApp/Services/Backend'),
        'MyApp\Services\Shared' => app_path('MyApp/Services/Shared'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | Your services need to live somewhere. Please specify here where they
    | live.
    |
    */
    'path' => app_path('MyApp/Services'),

    /*
    |--------------------------------------------------------------------------
    | Repositories Namespace
    |--------------------------------------------------------------------------
    |
    | If injecting repos, please speficy namespace here.
    |
    */
    'repositories_namespace' => 'MyApp\Repositories'

];