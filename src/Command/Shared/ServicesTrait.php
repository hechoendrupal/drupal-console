<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ServicesTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Style\DrupalStyle;

trait ServicesTrait
{
    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function servicesQuestion(DrupalStyle $io)
    {
        if ($io->confirm(
            $this->trans('commands.common.questions.services.confirm'),
            false
        )) {
            $service_collection = [];
            $io->writeln($this->trans('commands.common.questions.services.message'));
            $services = $this->container->getServiceIds();
            while (true) {
                $service = $io->choiceNoList(
                    $this->trans('commands.common.questions.services.name'),
                    $services,
                    null,
                    true
                );

                $service = trim($service);
                if (empty($service)) {
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
        if (!empty($services)) {
            $buildServices = [];
            foreach ($services as $service) {
                $class = get_class($this->container->get($service));
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

            return $buildServices;
        }

        return [];
    }
}
