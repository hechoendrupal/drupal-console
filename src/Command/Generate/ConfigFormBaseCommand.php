<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ConfigFormBaseCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Render\ElementInfoManager;

class ConfigFormBaseCommand extends FormCommand
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var FormGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var RouteProviderInterface
     */
    protected $routeProvider;

    /**
     * @var ElementInfoManager
     */
    protected $elementInfoManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * ConfigFormBaseCommand constructor.
     * @param Manager                $extensionManager
     * @param FormGenerator          $generator
     * @param StringConverter        $stringConverter
     * @param RouteProviderInterface $routeProvider
     * @param ElementInfoManager     $elementInfoManager
     * @param                        $appRoot
     * @param ChainQueue             $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        FormGenerator $generator,
        StringConverter $stringConverter,
        RouteProviderInterface $routeProvider,
        ElementInfoManager $elementInfoManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->routeProvider = $routeProvider;
        $this->elementInfoManager = $elementInfoManager;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct($extensionManager, $generator, $chainQueue, $stringConverter, $elementInfoManager, $routeProvider);
    }

    protected function configure()
    {
        $this->setFormType('ConfigFormBase');
        $this->setCommandName('generate:form:config');
        parent::configure();
    }
}
