<?php

/**
 * A test file for local configuration
 *
 */

return [
    'database' =>
    /** Test it
     * with comment
     **/
        [
            'hostname' => 'testhost',
            'username' => 'testuser',
            'password' => 'testpw',
            'database' => 'testdb',
            'charset' => 'utf8mb4',
        ],
    // another try
    /**
     * What about this
     */
    'config' =>
    // but with comment here
        [
            'admin_email' =>
            // and here
                'admin@test.it',
            'sitename' => 'Friendica Social Network',
            'register_policy' =>
            // and here too
                \Friendica\Module\Register::OPEN,
            'register_text' => '',
        ],
    'system'
    =>
        [
            'default_timezone' => 'UTC',
            'language' => 'en',
            'theme' => 'frio'
        ],
    'testcat' => [
        'testarr' => ['1','2','3'],
    ],
    // closing it
    \Friendica\App::class => [
        \Friendica\App\Mode::class => true
    ]
];
