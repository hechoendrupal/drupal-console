<?php

namespace Drupal\Console\Command\Shared;

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
     * @param bool        $showProfile
     *   If profiles should be discovered.
     *
     * @throws \Exception
     *   When no modules are found.
     *
     * @return string
     */
    public function moduleQuestion($showProfile = true)
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

        $module = $this->getIo()->choiceNoList(
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
     *
     * @throws \Exception
     *   When one or more requirements are not met.
     */
    public function moduleRequirement(array $module)
    {
        // TODO: Module dependencies should also be checked
        // for unmet requirements recursively.
        $fail = false;
        foreach ($module as $module_name) {
            module_load_install($module_name);
            if ($requirements = \Drupal::moduleHandler()->invoke($module_name, 'requirements', ['install'])) {
                foreach ($requirements as $requirement) {
                    if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
                        $this->getIo()->info("Module '{$module_name}' cannot be installed: " . $requirement['title'] . ' | ' . $requirement['value']);
                        $fail = true;
                    }
                }
            }
        }
        if ($fail) {
            throw new \Exception("Some module install requirements are not met.");
        }
    }

    /**
     * Get module name from user.
     *
     * @return mixed|string
     *   Module name.
     * @throws \Exception
     *   When module is not found.
     */
    public function getModuleOption()
    {
        $input = $this->getIo()->getInput();
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion();
            $input->setOption('module', $module);
        } else {
            $missing_modules = $this->validator->getMissingModules([$module]);
            if ($missing_modules) {
                throw new \Exception(
                    sprintf(
                        $this->trans(
                            'commands.module.download.messages.no-releases'
                        ),
                        $module
                    )
                );
            }
        }

        return $module;
    }
}
