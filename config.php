<?php
declare(strict_types=1);

namespace nicotine;

Registry::set('config', (object) [
    /*
    | Site name.
    */
    'siteName' => 'Nicotine Framework',

    /*
    | On PRODUCTION_MODE all emails sent with email() heper will be sent to the real address.
    | On STAGING_MODE all emails sent with email() heper will be redirected to this value.
    | On DEVELOPMENT_MODE arguments of the email() helper will be printed and no email will be sent.
    */
    'redirectEmailsTo' => 'admin@example.com',

    /*
    | This could be e.g. http://localhost:8080/my-project
    | Please exclude trailing slash from URL definition.
    */
    'baseHref' => 'http://nicotine-framework.local',

    /*
    | This clould be e.g. "html", or you can set it to an empty string.
    | Please exclude leading dot from the definition.
    */
    'urlSuffix' => 'html',

    /*
    | Possible values are:
    |   'DEVELOPMENT_MODE' = Show all errors, with SQL queries dump, statistics and hardware resources usage.
    |   'STAGING_MODE' = Show all errors, warnings and notices.
    |   'PRODUCTION_MODE' = Quiet mode. Note that errors are still logged, until you explicit disable 'logErrors' configuration directive.
    */
    'errorReporting' => 'DEVELOPMENT_MODE',

    /*
    | Web (Site and Admin), AJAX and CLI and API errors.
    | It is recommended to be always true.
    */
    'logErrors' => true,

    /*
    | Database server name, or IP address.
    */
    'dbHost' => '127.0.0.1',

    /*
    | Database port.
    */
    'dbPort' => 3306,

    /*
    | Database user.
    */
    'dbUser' => 'root',

    /*
    | Database password.
    */
    'dbPassword' => '',

    /*
    | Database name.
    */
    'dbName' => 'nicotine_framework',

    /*
    | Database charset.
    */
    'dbCharset' => 'utf8mb4',

    /*
    | Database collation.
    */
    'dbCollation' => 'utf8mb4_unicode_ci',

    /*
    | Home controller for site.
    */
    'homeController'  => 'Home',

    /*
    | Home action.
    */
    'homeAction' => 'index',

    /*
    | Login controller.
    */
    'loginController'  => 'Login',

    /*
    | Login action.
    */
    'loginAction' => 'index'
]);
