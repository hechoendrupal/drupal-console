<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Generator\Generator.
 */

namespace Drupal\AppConsole\Generator;

use Drupal\AppConsole\Utils\DrupalExtensionDiscovery;
use Drupal\AppConsole\Utils\StringUtils;

class Generator
{
    private $skeletonDirs;

    private $module_path;

    private $translator;

    private $files;

    private $learning = false;

    /**
     * Sets an array of directories to look for templates.
     *
     * The directories must be sorted from the most specific to the most
     * directory.
     *
     * @param array $skeletonDirs An array of skeleton dirs
     */
    public function setSkeletonDirs($skeletonDirs)
    {
        $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    protected function render($template, $parameters)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), [
          'debug' => true,
          'cache' => false,
          'strict_variables' => true,
          'autoescape' => false,
        ]);

        $twig->addFunction($this->getServicesAsParameters());
        $twig->addFunction($this->getServicesAsParametersKeys());
        $twig->addFunction($this->getArgumentsFromRoute());
        $twig->addFunction($this->getServicesClassInitialization());
        $twig->addFunction($this->getServicesClassInjection());
        $twig->addFunction($this->getTagsAsArray());
        $twig->addFunction($this->getTranslationAsYamlComment());
        $twig->addFilter($this->createMachineName());

        return $twig->render($template, $parameters);
    }

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     * @param null   $flag
     *
     * @return bool
     */
    protected function renderFile($template, $target, $parameters, $flag = null)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        if (file_put_contents($target, $this->render($template, $parameters), $flag)) {
            $this->files[] = str_replace(DRUPAL_ROOT.'/', '', $target);

            return true;
        }

        return false;
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    protected function renderView($template, $parameters)
    {
        return $this->render($template, $parameters);
    }

    public function getModulePath($module_name)
    {
        if (!$this->module_path) {
            /*
           * @todo Remove DrupalExtensionDiscovery subclass once
           * https://www.drupal.org/node/2503927 is fixed.
           */
            $discovery = new DrupalExtensionDiscovery(\Drupal::root());
            $discovery->reset();
            $result = $discovery->scan('module');
            $this->module_path = DRUPAL_ROOT.'/'.$result[$module_name]->getPath();
        }

        return $this->module_path;
    }

    public function getControllerPath($module_name)
    {
        return $this->getModulePath($module_name).'/src/Controller';
    }

    public function getTestPath($module_name, $test_type)
    {
        return $this->getModulePath($module_name).'/Tests/'.$test_type;
    }

    public function getFormPath($module_name)
    {
        return $this->getModulePath($module_name).'/src/Form';
    }

    public function getPluginPath($module_name, $plugin_type)
    {
        return $this->getModulePath($module_name).'/src/Plugin/'.$plugin_type;
    }

    public function getAuthenticationPath($module_name, $authentication_type)
    {
        return $this->getModulePath($module_name).'/src/Authentication/'.$authentication_type;
    }

    public function getCommandPath($module_name)
    {
        return $this->getModulePath($module_name).'/src/Command';
    }

    public function getSourcePath($module_name)
    {
        return $this->getModulePath($module_name).'/src';
    }

    public function getEntityPath($module_name)
    {
        return $this->getModulePath($module_name).'/src/Entity';
    }

    /**
     * @param string $module_name
     *
     * @return string
     */
    public function getTemplatePath($module_name)
    {
        return $this->getModulePath($module_name).'/templates';
    }

    /**
     * @param string $module_name
     *
     * @return string
     */
    public function getTranslationsPath($module_name)
    {
        return $this->getModulePath($module_name).'/config/translations';
    }

    /**
     * @return \Twig_SimpleFunction
     */
    public function getServicesAsParameters()
    {
        $servicesAsParameters = new \Twig_SimpleFunction('servicesAsParameters', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('%s $%s', $service['short'], $service['machine_name']);
            }

            return $returnValues;
        });

        return $servicesAsParameters;
    }

    /**
     * @return \Twig_SimpleFunction
     */
    public function getServicesAsParametersKeys()
    {
        $servicesAsParametersKeys = new \Twig_SimpleFunction('servicesAsParametersKeys', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('"@%s"', $service['name']);
            }

            return $returnValues;
        });

        return $servicesAsParametersKeys;
    }

    /**
     * @return \Twig_SimpleFunction
     */
    public function getArgumentsFromRoute()
    {
        $argumentsFromRoute = new \Twig_SimpleFunction('argumentsFromRoute', function ($route) {
            $returnValues = '';
            preg_match_all('/{(.*?)}/', $route, $returnValues);

            $returnValues = array_map(function ($value) {
                return sprintf('$%s', $value);
            }, $returnValues[1]);

            return $returnValues;
        });

        return $argumentsFromRoute;
    }

    /**
     * @return \Twig_SimpleFunction
     */
    public function getServicesClassInitialization()
    {
        $returnValue = new \Twig_SimpleFunction('serviceClassInitialization', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('    $this->%s = $%s;', $service['machine_name'], $service['machine_name']);
            }

            return implode(PHP_EOL, $returnValues);
        });

        return $returnValue;
    }

    /**
     * @return \Twig_SimpleFunction
     */
    public function getServicesClassInjection()
    {
        $returnValue = new \Twig_SimpleFunction('serviceClassInjection', function ($services) {
            $returnValues = [];
            foreach ($services as $service) {
                $returnValues[] = sprintf('      $container->get(\'%s\')', $service['name']);
            }

            return implode(','.PHP_EOL, $returnValues);
        });

        return $returnValue;
    }

    /**
     * @return \Twig_SimpleFunction
     */
    public function getTagsAsArray()
    {
        $returnValue = new \Twig_SimpleFunction('tagsAsArray', function ($tags) {
            $returnValues = [];
            foreach ($tags as $key => $value) {
                $returnValues[] = sprintf('%s: %s', $key, $value);
            }

            return $returnValues;
        });

        return $returnValue;
    }

    public function getTranslationAsYamlComment()
    {
        $returnValue = new \Twig_SimpleFunction('yaml_comment', function (\Twig_Environment $environment, $context, $key) {
            $message = $this->translator->trans($key);
            $messages = explode("\n", $message);
            $returnValues = [];
            foreach ($messages as $message) {
                $returnValues[] = '# '.$message;
            }

            $message = implode("\n", $returnValues);
            $environment->setLoader(new \Twig_Loader_String());

            return $environment->render($message, $context);
        }, [
          'needs_environment' => true,
          'needs_context' => true,
        ]);

        return $returnValue;
    }

    /**
     * @return \Twig_SimpleFilter
     */
    public function createMachineName()
    {
        $string = new StringUtils();

        return new \Twig_SimpleFilter('machine_name', function ($var) use ($string) {
            return $string->createMachineName($var);
        });
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator()
    {
        return $this->translator;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function setLearning($learning)
    {
        $this->learning = $learning;
    }

    public function isLearning()
    {
        return $this->learning;
    }
}
