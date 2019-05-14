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
     * Get module name from user.
     *
     * @return mixed|string
     *   Module name.

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
            $this->validateModule($module);
        }

        return $module;
    }

    /**
     * Validate module.
     *
     * @param string $module
     *   Module name.
     * @return string
     *   Module name.
     *
     * @throws \Exception
     *   When module is not found.
     */
    public function validateModule($module) {
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
        return $module;
    }
}
