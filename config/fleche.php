<?php

return [

    /*
     * self|cloud. The one switch between editions: marketing pages, billing,
     * the 7-day retention and hub publishing only exist on `cloud`.
     */
    'edition' => env('APP_EDITION', 'self'),

    /*
     * First account, read once by `fleche:install` on container boot. No
     * defaults on purpose: without them the setup screen asks instead.
     */
    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
     * The community hub always lives on the cloud instance. A self-hosted
     * instance browses and imports from it over HTTP.
     */
    'hub_url' => env('HUB_URL', 'https://fleche.io'),

    /** Points an author earns each time one of their packs is imported. */
    'hub_import_points' => 10,

    'cloud' => [
        /** One-time payment unlocking unlimited history, in cents (USD). */
        'lifetime_amount' => 3500,

        /** Days a non-paying cloud user keeps past todos. */
        'free_retention_days' => 7,
    ],

];
