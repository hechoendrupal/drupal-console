<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ServicesTrait.
 */

namespace Drupal\Console\Command\Shared;

trait ServicesTrait
{
    /**
     * @return mixed
     */
    public function servicesQuestion()
    {
        if ($this->getIo()->confirm(
            $this->trans('commands.common.questions.services.confirm'),
            false
        )
        ) {
            $service_collection = [];
            $this->getIo()->writeln($this->trans('commands.common.questions.services.message'));
            $services = $this->container->getServiceIds();

            while (true) {
                $service = $this->getIo()->choiceNoList(
                    $this->trans('commands.common.questions.services.name'),
                    $services,
                    '',
                    true
                );

                $service = trim($service);
                if (empty($service) || is_numeric($service)) {
                    break;
                }

                array_push($service_collection, $service);
                $service_key = array_search($service, $services, true);

                if ($service_key >= 0) {
                    unset($services[$service_key]);
                }
            }

            return $service_collection;
        }
    }

    /**
     * @param array $services
     *
     * @return array
     */
    public function buildServices($services)
    {
        $buildServices = [];
        if (!empty($services)) {
            foreach ($services as $service) {
                $class = get_class($this->container->get($service));
                $class = $this->getInterface($class);
                $shortClass = explode('\\', $class);
                $machineName = str_replace('.', '_', $service);
                $buildServices[$service] = [
                  'name' => $service,
                  'machine_name' => $machineName,
                  'camel_case_name' => $this->stringConverter->underscoreToCamelCase($machineName),
                  'class' => $class,
                  'short' => end($shortClass),
                ];
            }
        }

        return $buildServices;
    }

    /**
     * Gets class interface.
     *
     * @param string $class
     *   Class name.
     *
     * @return string
     *   Interface
     */
    private function getInterface($class) {
        $interfaceName = $class;
        $interfaces = class_implements($class);
        if (!empty($interfaces)) {
            if (count($interfaces) == 1) {
                $interfaceName = array_shift($interfaces);
            } elseif ($key = array_search($class . 'Interface', $interfaces)) {
                $interfaceName = $interfaces[$key];
            }
        }

        return $interfaceName;
    }
}
