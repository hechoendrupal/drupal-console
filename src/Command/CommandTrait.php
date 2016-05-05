<?php

namespace Drupal\Console\Command;

use Drupal\Console\Utils\Translator;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait CommandTrait
{
    /**
 * @var  Translator 
*/
    protected $translator;

    /**
     * StandAloneCommandTrait constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;

        parent::__construct();
    }

    public function get($key)
    {
        if (!$key) {
            return null;
        }

        if ($this->getApplication()->getContainer()->has($key)) {
            return $this->getApplication()->getContainer()->get($key);
        }
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        return $this->translator->trans($key);
    }

    /**
     * @return \Drupal\Console\Application;
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}
