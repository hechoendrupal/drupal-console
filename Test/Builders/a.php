<?php
namespace Drupal\Console\Test\Builders;

use Drupal\Console\Extension\Manager;

use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Utils\StringConverter;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Utils\Site;
use GuzzleHttp\Client;
use Prophecy\Prophet;

use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Generator\CommandGenerator;
use Drupal\Console\Generator\EntityBundleGenerator;
use Drupal\Console\Generator\EntityContentGenerator;
use Drupal\Console\Generator\EntityConfigGenerator;
use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Generator\ServiceGenerator;
use Drupal\Console\Generator\PermissionGenerator;
use Drupal\Console\Generator\ModuleGenerator;
use Drupal\Console\Generator\ControllerGenerator;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;

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
    public static function guzzleHttpClient()
    {
        return self::prophet()->prophesize(Client::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function connection()
    {
        return self::prophet()->prophesize(Connection::class);
    }


    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function stateInterface()
    {
        return self::prophet()->prophesize(StateInterface::class);
    }


    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function currentPathStack()
    {
        return self::prophet()->prophesize(CurrentPathStack::class);
    }


    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function cacheBackendInterface()
    {
        return self::prophet()->prophesize(CacheBackendInterface::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function inboundPathProcessorInterface()
    {
        return self::prophet()->prophesize(InboundPathProcessorInterface::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function cacheTagsInvalidator()
    {
        return self::prophet()->prophesize(CacheTagsInvalidator::class);
    }



    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function drupalApi()
    {
        return self::prophet()->prophesize(DrupalApi::class);
    }
    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function siteDrupal()
    {
        return self::prophet()->prophesize(Site::class);
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
        public static function entityConfigGenerator()
        {
            return self::prophet()->prophesize(EntityConfigGenerator::class);
        }


    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function permissionGenerator()
    {
        return self::prophet()->prophesize(PermissionGenerator::class);
    }


    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function moduleGenerator()
    {
        return self::prophet()->prophesize(ModuleGenerator::class);
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    public static function controllerGenerator()
    {
        return self::prophet()->prophesize(ControllerGenerator::class);
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
    public static function entityContentGenerator()
    {
        return self::prophet()->prophesize(EntityContentGenerator::class);
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
