<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Validator.
 */

namespace Drupal\Console\Utils;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Style\DrupalStyle;

class Validator
{
    const REGEX_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/';
    const REGEX_COMMAND_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+Command$/';
    const REGEX_CONTROLLER_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+Controller$/';
    const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';
    // This REGEX remove spaces between words
    const REGEX_REMOVE_SPACES = '/[\\s+]/';

    protected $appRoot;

    /*
     * TranslatorManager
     */
    protected $translatorManager;

    /**
     * Site constructor.
     *
     * @param Manager           $extensionManager
     * @param TranslatorManager $translatorManager
     */
    public function __construct(
        Manager $extensionManager,
        TranslatorManager $translatorManager
    ) {
        $this->extensionManager = $extensionManager;
        $this->translatorManager = $translatorManager;
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

    public function validateControllerName($class_name)
    {
        if (preg_match(self::REGEX_CONTROLLER_CLASS_NAME, $class_name)) {
            return $class_name;
        } elseif (preg_match(self::REGEX_CLASS_NAME, $class_name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Controller name "%s" is invalid, it must end with the word \'Controller\'',
                    $class_name
                )
            );
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Controller name "%s" is invalid, it must starts with a letter or underscore, followed by any number of letters, numbers, or underscores and then with the word \'Controller\'.',
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
        if (strlen($module_path) > 1 && $module_path[strlen($module_path)-1] == "/") {
            $module_path = substr($module_path, 0, -1);
        }

        if (is_dir($module_path)) {
            chmod($module_path, 0755);
            return $module_path;
        }


        if ($create && mkdir($module_path, 0755, true)) {
            return $module_path;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Path "%s" is invalid. You need to provide a valid path.',
                $module_path
            )
        );
    }

    public function validateMachineNameList($list)
    {
        $list_checked = [
            'success' => [],
            'fail' => [],
        ];

        if (empty($list)) {
            return [];
        }

        $list = explode(',', $this->removeSpaces($list));
        foreach ($list as $key => $module) {
            if (!empty($module)) {
                if (preg_match(self::REGEX_MACHINE_NAME, $module)) {
                    $list_checked['success'][] = $module;
                } else {
                    $list_checked['fail'][] = $module;
                }
            }
        }

        return $list_checked;
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

    /**
     * @param  string      $extensions_list
     * @param  string      $type
     * @param  DrupalStyle $io
     *
     * @return array
     */
    public function validateExtensions($extensions_list, $type, DrupalStyle $io)
    {
        $extensions = $this->validateMachineNameList($extensions_list);
        // Check if all extensions are available
        if ($extensions) {
            $checked_extensions = $this->extensionManager->checkExtensions($extensions['success'], $type);
            if (!empty($checked_extensions['no_extensions'])) {
                $io->warning(
                    sprintf(
                        $this->translatorManager->trans('validator.warnings.extension-unavailable'),
                        implode(', ', $checked_extensions['no_extensions'])
                    )
                );
            }
            $extensions = $extensions['success'];
        }

        return $extensions;
    }

    /**
   * Validate if http methods exist.
   *
   * @param array $httpMethods          Array http methods.
   * @param array $availableHttpMethods Array of available http methods.
   *
   * @return string
   */
    public function validateHttpMethods($httpMethods, $availableHttpMethods)
    {
        if (empty($httpMethods)) {
            return null;
        }

        $missing_methods = array_diff(array_values($httpMethods), array_keys($availableHttpMethods));
        if (!empty($missing_methods)) {
            throw new \InvalidArgumentException(sprintf('HTTP methods "%s" are invalid.', implode(', ', $missing_methods)));
        }

        return $httpMethods;
    }

    /**
     * Validates role existence or non existence.
     *
     * @param string $role
     *   Role machine name.
     * @param array $roles
     *   Array of available roles.
     * @param bool $checkExistence
     *   To check existence or non existence.
     *
     * @return string|null
     *   Role machine name.
     */
    private function validateRole($role, $roles, $checkExistence = true)
    {
        if (empty($roles)) {
            return null;
        }

        $roleExists = array_key_exists($role, $roles);
        $condition =  $checkExistence ? !$roleExists : $roleExists;
        if ($condition) {
            $errorMessage = $checkExistence ? "Role %s doesn't exist" : 'Role %s already exists';
            throw new \InvalidArgumentException(sprintf($errorMessage, $role));
        }

        return $role;
    }

    /**
     * Validate if the role already exists.
     *
     * @param string $role
     *   Role machine name.
     * @param array $roles
     *   Array of available roles.
     *
     * @return string|null
     *   Role machine name.
     */
    public function validateRoleExistence($role, $roles) {
        return $this->validateRole($role, $roles, true);
    }

    /**
     * Validate if the role doesn't exist.
     *
     * @param string $role
     *   Role machine name.
     * @param array $roles
     *   Array of available roles.
     *
     * @return string|null
     *   Role machine name.
     */
    public function validateRoleNotExistence($role, $roles) {
        return $this->validateRole($role, $roles, false);
    }
}
