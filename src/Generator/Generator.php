<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\Generator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Helper\HelperTrait;

class Generator
{
    use HelperTrait;

    /**
     * @var array
     */
    private $files;

    /**
     * @var bool
     */
    private $learning = false;

    /**
     * @var array
     */
    private $helperSet;

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     * @param null   $flag
     *
     * @return bool
     */
    protected function renderFile($template, $target, $parameters, $flag = null)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        if (file_put_contents($target, $this->getRenderHelper()->render($template, $parameters), $flag)) {
            $this->files[] = str_replace($this->getDrupalHelper()->getRoot().'/', '', $target);

            return true;
        }

        return false;
    }

    /**
     * @param $helperSet
     */
    public function setHelperSet($helperSet)
    {
        $this->helperSet = $helperSet;
    }

    /**
     * @return array
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param $learning
     */
    public function setLearning($learning)
    {
        $this->learning = $learning;
    }

    /**
     * @return bool
     */
    public function isLearning()
    {
        return $this->learning;
    }
}
