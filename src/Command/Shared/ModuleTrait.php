<?php

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ModuleTrait
 *
 * @package Drupal\Console\Command
 */
trait ModuleTrait
{
    /**
     * Ask the user to choose a module or profile.
     *
     * @param DrupalStyle $io
     *   Console interface.
     * @param bool        $showProfile
     *   If profiles should be discovered.
     *
     * @throws \Exception
     *   When no modules are found.
     *
     * @return string
     */
    public function moduleQuestion(DrupalStyle $io, $showProfile = true)
    {
        $modules = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showUninstalled()
            ->showNoCore()
            ->getList(true);

        if ($showProfile) {
            $profiles = $this->extensionManager->discoverProfiles()
                ->showInstalled()
                ->showUninstalled()
                ->showNoCore()
                ->showCore()
                ->getList(true);

            $modules = array_merge($modules, $profiles);
        }

        if (empty($modules)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }

        $module = $io->choiceNoList(
            $this->trans('commands.common.questions.module'),
            $modules
        );

        return $module;
    }

    /**
     * Verify that install requirements for a list of modules are met.
     *
     * @param string[]    $module
     *   List of modules to verify.
     * @param DrupalStyle $io
     *   Console interface.
     *
     * @throws \Exception
     *   When one or more requirements are not met.
     */
    public function moduleRequirement(array $module, DrupalStyle $io)
    {
        // TODO: Module dependencies should also be checked
        // for unmet requirements recursively.
        $fail = false;
        foreach ($module as $module_name) {
            module_load_install($module_name);
            if ($requirements = \Drupal::moduleHandler()->invoke($module_name, 'requirements', ['install'])) {
                foreach ($requirements as $requirement) {
                    if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
                        $io->info("Module '{$module_name}' cannot be installed: " . $requirement['title'] . ' | ' . $requirement['value']);
                        $fail = true;
                    }
                }
            }
        }
        if ($fail) {
            throw new \Exception("Some module install requirements are not met.");
        }
    }
}
