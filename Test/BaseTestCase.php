<?php

namespace Drupal\Console\Test;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
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
