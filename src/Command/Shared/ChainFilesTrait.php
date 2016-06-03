<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ChainFilesTrait.
 */

namespace Drupal\Console\Command\Shared;

use Symfony\Component\Finder\Finder;

/**
 * Class ChanFilesTrait
 * @package Drupal\Console\Command
 */
trait ChainFilesTrait
{
    private function getChainFiles($onlyFiles = false)
    {
        $config = $this->getApplication()->getConfig();

        $directories = [
            $config->getUserHomeDir() . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain',
            $this->getApplication()->getSite()->getSiteRoot() . DIRECTORY_SEPARATOR . 'console'. DIRECTORY_SEPARATOR .'chain',
            $this->getApplication()->getSite()->getSiteRoot() . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain',
        ];

        if ($this->get('site')->isInstalled()) {
            $modules = $this->getApplication()->getSite()->getModules(false, true, false, false, true);
            $themes = $this->getApplication()->getSite()->getThemes(false, true, false);

            foreach ($modules as $module) {
                $modulePath = sprintf(
                    '%s/%s/console/chain/',
                    $this->getApplication()->getSite()->getSiteRoot(),
                    $module->getPath()
                );

                if (is_dir($modulePath)) {
                    $directories[] = $modulePath;
                }
            }
            foreach ($themes as $theme) {
                $themePath = sprintf(
                    '%s/%s/console/chain',
                    $this->getApplication()->getSite()->getSiteRoot(),
                    $theme->getPath()
                );
                if (is_dir($themePath)) {
                    $directories[] = $themePath;
                }
            }
        }

        $chainFiles = [];
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in($directory);
            foreach ($finder as $file) {
                $chainFiles[$file->getPath()][] = sprintf(
                    '%s/%s',
                    $directory,
                    $file->getBasename()
                );
            }
        }

        if ($onlyFiles) {
            $files = [];
            foreach ($chainFiles as $chainDirectory => $chainFileList) {
                $files = array_merge($files, $chainFileList);
            }
            return $files;
        }

        return $chainFiles;
    }

    private function extractPlaceHolders($chainContent, $identifier)
    {
        $placeHolders = [];
        $regex = '/\\'.$identifier.'{{(.*?)}}/';
        preg_match_all(
            $regex,
            $chainContent,
            $placeHolders
        );

        if (!$placeHolders) {
            return [];
        }

        return array_unique($placeHolders[1]);
    }

    private function extractInlinePlaceHolders($chainContent)
    {
        return $this->extractPlaceHolders($chainContent, '%');
    }

    private function extractEnvironmentPlaceHolders($chainContent)
    {
        return $this->extractPlaceHolders($chainContent, '$');
    }
}
