<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ConfigFormBaseCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Utils\TranslatorManager;
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
     * @var Validator
     */
    protected $validator;

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
     *
     * @param TranslatorManager      $translator
     * @param Manager                $extensionManager
     * @param FormGenerator          $generator
     * @param StringConverter        $stringConverter
     * @param Validator              $validator
     * @param RouteProviderInterface $routeProvider
     * @param ElementInfoManager     $elementInfoManager
     * @param $appRoot
     * @param ChainQueue             $chainQueue
     */
    public function __construct(
        TranslatorManager $translator,
        Manager $extensionManager,
        FormGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        RouteProviderInterface $routeProvider,
        ElementInfoManager $elementInfoManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->routeProvider = $routeProvider;
        $this->elementInfoManager = $elementInfoManager;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct($translator, $extensionManager, $generator, $chainQueue, $stringConverter, $validator, $elementInfoManager, $routeProvider);
    }

    protected function configure()
    {
        $this->setFormType('ConfigFormBase');
        $this->setCommandName('generate:form:config');
        $this->setAliases(['gfc']);
        parent::configure();
    }
}
