<?php

namespace Drupal\Console\Command;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait CommandTrait
{
    /**
 * @var  \Drupal\Console\Helper\TranslatorHelper 
*/
    protected $translator;

    /**
     * CommandTrait constructor.
     * @param \Drupal\Console\Helper\TranslatorHelper $translator
     */
    public function __construct(
        \Drupal\Console\Helper\TranslatorHelper $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if ($this->translator) {
            return $this->translator->trans($key);
        }

        return $key;
    }

    /**
     * @return \Drupal\Console\Application;
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}
