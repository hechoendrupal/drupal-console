<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ContainerDebugCommand.
 */

namespace Drupal\Console\Command\Debug;

use Drupal\Console\Core\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ContainerCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class ContainerCommand extends ContainerAwareCommand
{

    const BLUE = 'blue';
    const CYAN = 'cyan';
    const GREEN = 'green';
    const MAGENTA = 'magenta';
    const RED = 'red';
    const YELLOW = 'yellow';
    const WHITE = 'white';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:container')
            ->setDescription($this->trans('commands.debug.container.description'))
            ->addOption(
                'parameters',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.debug.container.arguments.service')
            )
            ->addArgument(
                'service',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.container.arguments.service')
            )->addArgument(
                'method',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.container.arguments.method')
            )->addArgument(
                'arguments',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.container.arguments.arguments')
            )->addOption(
                'tag',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.container.options.tag')
            )
            ->setAliases(['dco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument('service');
        $parameters = $input->getOption('parameters');
        $tag = $input->getOption('tag');
        $method = $input->getArgument('method');
        $args = $input->getArgument('arguments');

        if ($parameters) {
            $parameterList = $this->getParameterList();
            ksort($parameterList);
            $this->getIo()->write(Yaml::dump(['parameters' => $parameterList], 4, 2));

            return 0;
        }

        if ($method) {
            $tableHeader = [];
            $callbackRow = $this->getCallbackReturnList($service, $method, $args);
            $this->getIo()->table($tableHeader, $callbackRow, 'compact');

            return 0;
        } else {
            $tableHeader = [];
            if ($service) {
                $tableRows = $this->getServiceDetail($service);
                $this->getIo()->table($tableHeader, $tableRows, 'compact');

                return 0;
            }

            $tableHeader = [
                $this->trans('commands.debug.container.messages.service-id'),
                $this->trans('commands.debug.container.messages.class-name')
            ];
            $tableRows = $this->getServiceList($tag);
            $this->getIo()->table($tableHeader, $tableRows, 'compact');
        }

        return 0;
    }

    /**
     * Get callback list.
     *
     * @param string $service
     *   Service name.
     * @param string $method
     *   Methods name.
     * @param array $args
     *   Arguments.
     *
     * @return array
     *   List of callbacks.
     */
    private function getCallbackReturnList($service, $method, $args)
    {
        if ($args != null) {
            $parsedArgs = json_decode($args, true);
            if (!is_array($parsedArgs)) {
                $parsedArgs = explode(',', $args);
            }
        } else {
            $parsedArgs = null;
        }
        $serviceInstance = \Drupal::service($service);

        if (!method_exists($serviceInstance, $method)) {
            throw new \Symfony\Component\DependencyInjection\Exception\BadMethodCallException($this->trans('commands.debug.container.errors.method-not-exists'));

            return $serviceDetail;
        }
        $serviceDetail[] = [
            $this->addGreenTranslationWrapper('commands.debug.container.messages.service'),
            $this->addWrapper($service),
        ];
        $serviceDetail[] = [
            $this->addGreenTranslationWrapper('commands.debug.container.messages.class'),
            $this->addWrapper(get_class($serviceInstance)),
        ];
        $methods = [$method];
        $this->extendArgumentList($serviceInstance, $methods);
        $serviceDetail[] = [
            $this->addGreenTranslationWrapper('commands.debug.container.messages.method'),
            $this->addWrapper($methods[0]),
        ];
        if ($parsedArgs) {
            $serviceDetail[] = [
                $this->addGreenTranslationWrapper('commands.debug.container.messages.arguments'),
                $this->addWrapper(json_encode($parsedArgs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            ];
        }
        $return = call_user_func_array([$serviceInstance,$method], $parsedArgs);
        $serviceDetail[] = [
            $this->addGreenTranslationWrapper('commands.debug.container.messages.return'),
            $this->addWrapper(json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
        ];
        return $serviceDetail;
    }

    /**
     * Get service list.
     *
     * @param string $tag
     *   Tag.
     *
     * @return array
     *   Array of services.
     */
    private function getServiceList($tag = null)
    {
        if ($tag) {
            return $this->getServiceListByTag($tag);
        }

        $services = [];
        $serviceDefinitions = $this->container->getDefinitions();

        foreach ($serviceDefinitions as $serviceId => $serviceDefinition) {
            $services[] = [$serviceId, $serviceDefinition->getClass()];
        }
        usort($services, [$this, 'compareService']);
        return $services;
    }

    /**
     * Get service list by a tag.
     *
     * @param string $tag
     *   Tag.
     *
     * @return array
     *   Array of services.
     */
    private function getServiceListByTag($tag)
    {
        $services = [];
        $serviceIds = [];
        $serviceDefinitions = $this->container->getDefinitions();

        foreach ($tag as $tagId) {
            $serviceIds = array_merge(
                $serviceIds,
                array_keys($this->container->findTaggedServiceIds($tagId))
            );
        }

        foreach ($serviceIds as $serviceId) {
            $serviceDefinition = $serviceDefinitions[$serviceId];
            if ($serviceDefinition) {
                $services[] = [$serviceId, $serviceDefinition->getClass()];
            }
        }

        usort($services, [$this, 'compareService']);
        return $services;
    }

    /**
     * Compares the values.
     *
     * @param string $a
     *   First value.
     * @param string $b
     *   Second value.
     *
     * @return int
     *   Result.
     */
    private function compareService($a, $b)
    {
        return strcmp($a[0], $b[0]);
    }

    private function getServiceDetail($service)
    {
        $serviceInstance = $this->get($service);
        $serviceDetail = [];
        $class_name = get_class($serviceInstance);

        if ($serviceInstance) {
            $serviceDetail[] = [
                $this->addGreenTranslationWrapper('commands.debug.container.messages.service'),
                $this->addTranslationWrapper('commands.debug.container.messages.service'),
            ];
            $serviceDetail[] = [
                $this->addGreenTranslationWrapper('commands.debug.container.messages.class'),
                $this->addTranslationWrapper('commands.debug.container.messages.class'),
            ];
            $interface = str_replace('{  }', '', Yaml::dump(class_implements($serviceInstance)));
            if (!empty($interface)) {
                $serviceDetail[] = [
                    $this->addGreenTranslationWrapper('commands.debug.container.messages.interface'),
                    $this->addWrapper($interface),
                ];
            }
            if ($parent = get_parent_class($serviceInstance)) {
                $serviceDetail[] = [
                    $this->addGreenTranslationWrapper('commands.debug.container.messages.parent'),
                    $this->addWrapper($parent),
                ];
            }
            if ($vars = get_class_vars($class_name)) {
                $serviceDetail[] = [
                    $this->addGreenTranslationWrapper('commands.debug.container.messages.variables'),
                    $this->addWrapper(Yaml::dump($vars)),
                ];
            }
            if ($methods = get_class_methods($class_name)) {
                sort($methods);
                $this->extendArgumentList($serviceInstance, $methods);
                $serviceDetail[] = [
                    $this->addGreenTranslationWrapper('commands.debug.container.messages.methods'),
                    $this->addWrapper(implode("\n", $methods)),
                ];
            }
        } else {
            throw new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException($service);

            return $serviceDetail;
        }

        return $serviceDetail;
    }

    /**
     * Adds a wrapper with a color
     *
     * @param string $text
     *   Text.
     * @param string $color
     *   Color.
     *
     * @return string
     *   Result of the wrapping.
     */
    private function addWrapper($text, $color = ContainerCommand::YELLOW)
    {
        return "<fg=$color>$text</>";
    }

    /**
     * Adds green color wrapper.
     *
     * @param string $translationString
     *   Translation string.
     *
     * @return string
     *   Result of the wrapping.
     */
    private function addGreenTranslationWrapper($translationString)
    {
        return $this->addTranslationWrapper($translationString, ContainerCommand::GREEN);
    }

    /**
     * Adds green color wrapper.
     *
     * @param string $translationString
     *   Translation string.
     *
     * @return string
     *   Result of the wrapping.
     */
    private function addTranslationWrapper($translationString, $color = ContainerCommand::YELLOW) {
        return $this->addWrapper($this->trans($translationString), $color);
    }

    private function extendArgumentList($serviceInstance, &$methods)
    {
        foreach ($methods as $k => $m) {
            $reflection = new \ReflectionMethod($serviceInstance, $m);
            $params = $reflection->getParameters();
            $p = [];

            for ($i = 0; $i < count($params); $i++) {

                if ($params[$i]->isDefaultValueAvailable()) {
                    $defaultVar = $params[$i]->getDefaultValue();
                    $defaultVar = ' = ' . $this->addWrapper(str_replace(["\n",'array ('], ['', 'array('], var_export($defaultVar, true)), ContainerCommand::MAGENTA);
                } else {
                    $defaultVar = '';
                }

                if (method_exists($params[$i], 'hasType') && method_exists($params[$i], 'getType')) {
                    if ($params[$i]->hasType()) {
                        $defaultType = $this->addWrapper( strval($params[$i]->getType()), ContainerCommand::WHITE) . ' ';
                    } else {
                        $defaultType = '';
                    }
                } else {
                    $defaultType = '';
                }

                if ($params[$i]->isPassedByReference()) {
                    $parameterReference = $this->addWrapper('&');
                } else {
                    $parameterReference = '';
                }

                $p[] = $defaultType . $parameterReference . $this->addWrapper('$' . $params[$i]->getName(), ContainerCommand::RED) . $defaultVar;
            }

            if ($reflection->isPublic()) {
                $methods[$k] = $this->addWrapper($methods[$k], ContainerCommand::CYAN) . $this->addWrapper('(', ContainerCommand::BLUE) . implode(', ', $p) . $this->addWrapper(')', ContainerCommand::BLUE);
            }
        }
    }

    /**
     * Get parameter list.
     *
     * @return array
     *   Array with parameter.
     */
    private function getParameterList()
    {
        return array_filter(
            $this->container->getParameterBag()->all(), function ($name) {
                if (preg_match('/^container\./', $name)) {
                    return false;
                }
                if (preg_match('/^drupal\./', $name)) {
                    return false;
                }
                if (preg_match('/^console\./', $name)) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_KEY
        );
    }
}
