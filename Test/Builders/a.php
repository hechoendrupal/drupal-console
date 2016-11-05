<?php

namespace Drupal\Console\Test\Builders;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Generator\EntityBundleGenerator;
use Prophecy\Prophet;

class a
{
    /** @var Prophet */
    private static $prophet;

    /**
     * @return Manager
     */
    public static function extensionManager()
    {
        return self::prophet()->prophesize(Manager::class)->reveal();
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function entityBundleGenerator()
    {
        return self::prophet()->prophesize(EntityBundleGenerator::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function authenticationProviderGenerator()
    {
        return self::prophet()->prophesize(AuthenticationProviderGenerator::class);
    }

    /**
     * @return Prophet
     */
    private static function prophet()
    {
        if (!self::$prophet) {
            self::$prophet = new Prophet();
        }

        return self::$prophet;
    }
}
