<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garde-fou : avec une configuration en cache (php artisan optimize / config:cache),
     * phpunit.xml est ignoré et RefreshDatabase viderait la vraie base de données.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app['config']->get('database.default') !== 'sqlite') {
            throw new RuntimeException(
                'Tests arrêtés : la configuration est en cache. Lancez « php artisan optimize:clear » puis relancez les tests.'
            );
        }

        return $app;
    }
}
