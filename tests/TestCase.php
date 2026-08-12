<?php

namespace Aldhi88\StarterKit\Tests;

use Aldhi88\StarterKit\Providers\Starter\StarterInstallerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [StarterInstallerServiceProvider::class];
    }
}
