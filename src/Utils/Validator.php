<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Validator.
 */

namespace Drupal\Console\Utils;

use Drupal\Console\Extension\Manager;

class Validator
{
    const REGEX_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/';
    const REGEX_COMMAND_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+Command$/';
    const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';
    // This REGEX remove spaces between words
    const REGEX_REMOVE_SPACES = '/[\\s+]/';

    protected $appRoot;

    /**
     * Site constructor.
     * @param Manager extensionManager
     */
    public function __construct(Manager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    public function validateModuleName($module)
    {
        if (!empty($module)) {
            return $module;
        } else {
            throw new \InvalidArgumentException(sprintf('Module name "%s" is invalid.', $module));
        }
    }

    public function validateClassName($class_name)
    {
        if (preg_match(self::REGEX_CLASS_NAME, $class_name)) {
            return $class_name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Class name "%s" is invalid, it must starts with a letter or underscore, followed by any number of letters, numbers, or underscores.',
                    $class_name
                )
            );
        }
    }

    public function validateBundleTitle($bundle_title)
    {
        if (!empty($bundle_title)) {
            return $bundle_title;
        } else {
            throw new \InvalidArgumentException(sprintf('Bundle title "%s" is invalid.', $bundle_title));
        }
    }

    public function validateCommandName($class_name)
    {
        if (preg_match(self::REGEX_COMMAND_CLASS_NAME, $class_name)) {
            return $class_name;
        } elseif (preg_match(self::REGEX_CLASS_NAME, $class_name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Command name "%s" is invalid, it must end with the word \'Command\'',
                    $class_name
                )
            );
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Command name "%s" is invalid, it must starts with a letter or underscore, followed by any number of letters, numbers, or underscores and then with the word \'Command\'.',
                    $class_name
                )
            );
        }
    }

    public function validateMachineName($machine_name)
    {
        if (preg_match(self::REGEX_MACHINE_NAME, $machine_name)) {
            return $machine_name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Machine name "%s" is invalid, it must contain only lowercase letters, numbers and underscores.',
                    $machine_name
                )
            );
        }
    }

    public function validateModulePath($module_path, $create = false)
    {
        if (!is_dir($module_path)) {
            if ($create && mkdir($module_path, 0755, true)) {
                return $module_path;
            }

            throw new \InvalidArgumentException(
                sprintf(
                    'Module path "%s" is invalid. You need to provide a valid path.',
                    $module_path
                )
            );
        }

        return $module_path;
    }

    public function validateModuleDependencies($dependencies)
    {
        $dependencies_checked = array(
          'success' => array(),
          'fail' => array(),
        );

        if (empty($dependencies)) {
            return array();
        }

        $dependencies = explode(',', $this->removeSpaces($dependencies));
        foreach ($dependencies as $key => $module) {
            if (!empty($module)) {
                if (preg_match(self::REGEX_MACHINE_NAME, $module)) {
                    $dependencies_checked['success'][] = $module;
                } else {
                    $dependencies_checked['fail'][] = $module;
                }
            }
        }

        return $dependencies_checked;
    }

    /**
     * Validate if service name exist.
     *
     * @param string $service  Service name
     * @param array  $services Array of services
     *
     * @return string
     */
    public function validateServiceExist($service, $services)
    {
        if ($service == '') {
            return null;
        }

        if (!in_array($service, array_values($services))) {
            throw new \InvalidArgumentException(sprintf('Service "%s" is invalid.', $service));
        }

        return $service;
    }

    /**
     * Validate if service name exist.
     *
     * @param string $service  Service name
     * @param array  $services Array of services
     *
     * @return string
     */
    public function validatePluginManagerServiceExist($service, $services)
    {
        if ($service == '') {
            return null;
        }

        if (!in_array($service, array_values($services))) {
            throw new \InvalidArgumentException(sprintf('Plugin "%s" is invalid.', $service));
        }

        return $service;
    }

    /**
     * Validate if event name exist.
     *
     * @param string $event  Event name
     * @param array  $events Array of events
     *
     * @return string
     */
    public function validateEventExist($event, $events)
    {
        if ($event == '') {
            return null;
        }

        if (!in_array($event, array_values($events))) {
            throw new \InvalidArgumentException(sprintf('Event "%s" is invalid.', $event));
        }

        return $event;
    }

    /**
     * Validates if class name have spaces between words.
     *
     * @param string $name
     *
     * @return string
     */
    public function validateSpaces($name)
    {
        $string = $this->removeSpaces($name);
        if ($string == $name) {
            return $name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The name "%s" is invalid, spaces between words are not allowed.',
                    $name
                )
            );
        }
    }

    public function removeSpaces($name)
    {
        return preg_replace(self::REGEX_REMOVE_SPACES, '', $name);
    }

    /**
     * @param $moduleList
     * @return array
     */
    public function getMissingModules($moduleList)
    {
        $modules = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showUninstalled()
            ->showNoCore()
            ->showCore()
            ->getList(true);

        return array_diff($moduleList, $modules);
    }

    /**
     * @param $moduleList
     * @return array
     */
    public function getUninstalledModules($moduleList)
    {
        $modules = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->showCore()
            ->getList(true);

        return array_diff($moduleList, $modules);
    }
}
