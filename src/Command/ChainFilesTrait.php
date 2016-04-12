<?php

/**
 * @file
 * Contains Drupal\Console\Command\ChainFilesTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Finder\Finder;

/**
 * Class EventsTrait
 * @package Drupal\Console\Command
 */
trait ChainFilesTrait
{
    protected function getChainFiles()
    {
        $config = $this->getApplication()->getConfig();
        $directories = [
            $config->getUserHomeDir() . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain',
            $this->$this->getSite()->getSiteRoot() . DIRECTORY_SEPARATOR . 'console'. DIRECTORY_SEPARATOR .'chain',
            $this->$this->getSite()->getSiteRoot() . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain',
        ];


        if ($this->getDrupalHelper()->isInstalled()) {
            $modules = $this->getSite()->getModules(false, true, false, false, true);
            $themes = $this->getSite()->getThemes(false, true, false);

            foreach ($modules as $module) {
                $modulePath = sprintf(
                    '%s/%s/console/chain/',
                    $this->getSite()->getSiteRoot(),
                    $module->getPath()
                );

                if (is_dir($modulePath)) {
                    $directories[] = $modulePath;
                }
            }
            foreach ($themes as $theme) {
                $themePath = sprintf(
                    '%s/%s/console/chain',
                    $this->getSite()->getSiteRoot(),
                    $theme->getPath()
                );
                if (is_dir($themePath)) {
                    $directories[] = $themePath;
                }
            }
        }

        $files = [];
        foreach ($directories as $directory) {
            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in($directory);
            foreach ($finder as $file) {
                $files[$file->getPath()][] = $file->getBasename();
            }
        }

        return $files;
    }
}
