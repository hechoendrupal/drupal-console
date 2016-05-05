<?php

namespace Drupal\Console\Test;

use Symfony\Component\Console\Helper\HelperSet;
use Drupal\Console\Helper\TwigRendererHelper;
use Drupal\Console\Helper\HelperTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Console\Helper\ContainerHelper;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    use HelperTrait;

    public $dir;

    /**
     * @var \Symfony\Component\Console\Helper\HelperSet
     */
    protected $helperSet;

    protected function setup()
    {
        $this->setUpTemporaryDirectory();
    }

    public function setUpTemporaryDirectory()
    {
        $this->dir = sys_get_temp_dir() . "/modules";
    }

    public function getHelperSet($input = null)
    {
        if (!$this->helperSet) {
            $stringHelper = $this->getMockBuilder('Drupal\Console\Helper\StringHelper')
                ->disableOriginalConstructor()
                ->setMethods(['createMachineName'])
                ->getMock();

            $stringHelper->expects($this->any())
                ->method('createMachineName')
                ->will($this->returnArgument(0));

            $validator = $this->getMockBuilder('Drupal\Console\Helper\ValidatorHelper')
                ->disableOriginalConstructor()
                ->setMethods(['validateModuleName'])
                ->getMock();

            $validator->expects($this->any())
                ->method('validateModuleName')
                ->will($this->returnArgument(0));

            $translator = $this->getTranslatorHelper();

            $chain = $this
                ->getMockBuilder('Drupal\Console\Helper\ChainCommandHelper')
                ->disableOriginalConstructor()
                ->setMethods(['addCommand', 'getCommands'])
                ->getMock();

            $drupal = $this
                ->getMockBuilder('Drupal\Console\Helper\DrupalHelper')
                ->setMethods(['isBootable', 'getDrupalRoot'])
                ->getMock();

            $siteHelper = $this
                ->getMockBuilder('Drupal\Console\Helper\SiteHelper')
                ->disableOriginalConstructor()
                ->setMethods(['setModulePath', 'getModulePath'])
                ->getMock();

            $siteHelper->expects($this->any())
                ->method('getModulePath')
                ->will($this->returnValue($this->dir));

            $consoleRoot = __DIR__.'/../';
            $container = new ContainerBuilder();
            $loader = new YamlFileLoader($container, new FileLocator($consoleRoot));
            $loader->load('services.yml');

            $this->helperSet = new HelperSet(
                [
                    'renderer' => new TwigRendererHelper(),
                    'string' => $stringHelper,
                    'validator' => $validator,
                    'translator' => $translator,
                    'site' => $siteHelper,
                    'chain' => $chain,
                    'drupal' => $drupal,
                    'container' => new ContainerHelper($container),
                ]
            );
        }

        return $this->helperSet;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input . str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }

    public function getTranslatorHelper()
    {
        $translatorHelper = $this
            ->getMockBuilder('Drupal\Console\Helper\TranslatorHelper')
            ->disableOriginalConstructor()
            ->setMethods(['loadResource', 'trans', 'getMessagesByModule', 'writeTranslationsByModule'])
            ->getMock();

        $translatorHelper->expects($this->any())
            ->method('getMessagesByModule')
            ->will($this->returnValue([]));

        return $translatorHelper;
    }
}
