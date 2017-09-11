<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Services namespaces and locations
    |--------------------------------------------------------------------------
    |
    | We might want to divide the services used in our app in different folders
    | and namespaces. For instance, you might have services that are used only
    | by your backend app, while other services might be shared between your
    | backend app and your frontend web app.
    |
    | Please specify here the different namespaces and locations here.
    |
    */
    'groups' => [
        'App\MyApp\Services\Frontend' => app_path('MyApp/Services/Frontend'),
        'App\MyApp\Services\Backend' => app_path('MyApp/Services/Backend'),
        'App\MyApp\Services\Shared' => app_path('MyApp/Services/Shared'),
    ],
];