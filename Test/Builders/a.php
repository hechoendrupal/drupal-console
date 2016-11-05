<?php

namespace Drupal\Console\Test\Builders;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Generator\CommandGenerator;
use Drupal\Console\Generator\EntityBundleGenerator;
use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Generator\ServiceGenerator;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Utils\StringConverter;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\Routing\RouteProvider;
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
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function commandGenerator()
    {
        return self::prophet()->prophesize(CommandGenerator::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function stringConverter()
    {
        return self::prophet()->prophesize(StringConverter::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function formGenerator()
    {
        return self::prophet()->prophesize(FormGenerator::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function routeProvider()
    {
        return self::prophet()->prophesize(RouteProvider::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function elementInfoManager()
    {
        return self::prophet()->prophesize(ElementInfoManager::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function chainQueue()
    {
        return self::prophet()->prophesize(ChainQueue::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function serviceGenerator()
    {
        return self::prophet()->prophesize(ServiceGenerator::class);
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
