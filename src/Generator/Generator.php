<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\Generator.
 */

namespace Drupal\Console\Generator;

class Generator
{
    private $files;

    private $learning = false;

    private $helpers;

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

        if (file_put_contents($target, $this->getRenderer()->render($template, $parameters), $flag)) {
            $this->files[] = str_replace($this->getDrupalHelper()->getDrupalRoot().'/', '', $target);

            return true;
        }

        return false;
    }

    public function getSite()
    {
        return $this->getHelpers()->get('site');
    }

    public function getRenderer()
    {
        return $this->getHelpers()->get('renderer');
    }

    /**
     * @return \Drupal\Console\Helper\DrupalHelper
     */
    public function getDrupalHelper()
    {
        return $this->getHelpers()->get('drupal');
    }

    public function setHelpers($helpers)
    {
        $this->helpers = $helpers;
    }

    public function getHelpers()
    {
        return $this->helpers;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function setLearning($learning)
    {
        $this->learning = $learning;
    }

    public function isLearning()
    {
        return $this->learning;
    }
}
