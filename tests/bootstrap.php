<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

passthru('APP_ENV=test php bin/console doctrine:database:create --if-not-exists --env=test');
passthru('APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction --env=test');
