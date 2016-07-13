<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\CommandTrait.
 */

namespace Drupal\Console\Command\Shared;

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
     * @param $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param $key
     * @return null|object
     */
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
        if (!$this->translator) {
            return $key;
        }

        return $this->translator->trans($key);
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        $description = sprintf(
            'commands.%s.description',
            str_replace(':', '.', $this->getName())
        );

        if (parent::getDescription()==$description) {
            return $this->trans($description);
        }

        return parent::getDescription();
    }

    /**
     * @return \Drupal\Console\Application;
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}
