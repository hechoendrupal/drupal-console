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

    /**
     * Converts a module name in Composer format to its components.
     *
     * @param string $module
     *   Module name.
     *
     * @return string[]
     *   An keyed array with the following keys:
     *   - namespace: the Composer namespace of the package; when not specified
     *     in input, defaults to 'drupal'.
     *   - name: the the module name (equalling to the Composer package name).
     *   - constraint: the Composer constraint; when not specified, defaults to
     *     null.
     */
    public function getModuleComposerComponents($module) {
        $temp = explode('/', $module);
        if (count($temp) === 1) {
            $package['namespace'] = 'drupal';
            $package['name'] = $temp[0];
        } else {
            $package['namespace'] = $temp[0];
            $package['name'] = $temp[1];
        }
        $temp = explode(':', $package['name']);
        if (count($temp) === 1) {
            $package['constraint'] = null;
        } else {
            $package['name'] = $temp[0];
            $package['constraint'] = $temp[1];
        }
        return $package;
    }

    /**
     * Converts an array of mixed module names to Composer package syntax.
     *
     * @param string[] $modules
     *   A list of Drupal modules, that can be namespaced and contain
     *   Composer compliant version constraints. For example the following
     *   syntax is equivalent:
     *    "token"
     *    "token:^1"
     *    "drupal/token"
     *    "drupal/token:^1"
     *
     * @return array
     *   An associative array with the keys being the modules in Drupal syntax
     *   and values the modules as Composer packages, for example:
     *    "token" => "drupal/token"
     *    "pagerer" => "drupal/pagerer:^1"
     */
    public function composerQualifyModuleNames(array $modules) {
        $ret = [];
        foreach ($modules as $module) {
            $package = $this->getModuleComposerComponents($module);
            $temp = $package['namespace'] . '/' . $package['name'];
            if (isset($package['constraint'])) {
                $temp .= ':' . $package['constraint'];
            }
            $ret[$package['name']] = $temp;
        }
        return $ret;
    }
}
