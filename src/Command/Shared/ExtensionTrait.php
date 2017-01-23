<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ExtensionTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ExtensionTrait
 *
 * @package Drupal\Console\Command
 */
trait ExtensionTrait
{

    /**
     * @param DrupalStyle $io
     * @param bool|true   $module
     * @param bool|true   $theme
     * @param bool|true   $profile
     *
     * @return string
     *
     * @throws \Exception
     */
    public function extensionQuestion(DrupalStyle $io, $module=true, $theme=false, $profile=false)
    {
        $modules = [];
        $themes = [];
        $profiles = [];
        if ($module) {
            $modules = $this->extensionManager->discoverModules()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->getList();
        }

        if ($theme) {
            $themes = $this->extensionManager->discoverThemes()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->getList();
        }

        if ($profile) {
            $profiles = $this->extensionManager->discoverProfiles()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->showCore()
                ->getList();
        }

        $extensions = array_merge(
            $modules,
            $themes,
            $profiles
        );

        if (empty($extensions)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }

        $extension = $io->choiceNoList(
            $this->trans('commands.common.questions.extension'),
            array_keys($extensions)
        );

        return $extensions[$extension];
    }

    /**
     * @param DrupalStyle $io
     *
     * @return string
     *
     * @throws \Exception
     */
    public function extensionTypeQuestion(DrupalStyle $io)
    {
        $extensionType = $io->choiceNoList(
            $this->trans('commands.common.questions.extension-type'),
            array_keys(['module', 'theme', 'profile'])
        );

        return $extensionType;
    }
}
