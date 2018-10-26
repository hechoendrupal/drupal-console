<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ExtensionTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class ExtensionTrait
 *
 * @package Drupal\Console\Command
 */
trait ExtensionTrait
{

    /**
     * @param bool|true   $module
     * @param bool|true   $theme
     * @param bool|true   $profile
     *
     * @return string
     *
     * @throws \Exception
     */
    public function extensionQuestion($module=true, $theme=false, $profile=false)
    {
        $modules = [];
        $themes = [];
        $profiles = [];
        if ($module) {
            $modules = $this->extensionManager->discoverModules()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->getList(false);
        }

        if ($theme) {
            $themes = $this->extensionManager->discoverThemes()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->getList(false);
        }

        if ($profile) {
            $profiles = $this->extensionManager->discoverProfiles()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->showCore()
                ->getList(false);
        }

        $extensions = array_merge(
            $modules,
            $themes,
            $profiles
        );

        if (empty($extensions)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }

        $extension = $this->getIo()->choiceNoList(
            $this->trans('commands.common.questions.extension'),
            array_keys($extensions)
        );

        return $extensions[$extension];
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function extensionTypeQuestion()
    {
        $extensionType = $this->getIo()->choiceNoList(
            $this->trans('commands.common.questions.extension-type'),
            array_keys(['module', 'theme', 'profile'])
        );

        return $extensionType;
    }
}
