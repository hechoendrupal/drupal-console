<?php
/**
 * @file
 * Containt Drupal\AppConsole\Command\Helper\ServicesTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

trait ServicesTrait
{
  /**
   * @param  $output
   * @param  $dialog
   */
  public function servicesQuestion($output, $dialog)
  {
    if ($dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Do you like add service(s)', 'yes', '?'),
      true
    )) {
      $service_collection = [];
      $output->writeln([
        '',
        'You can add some services, type the name or use keyup and keydown',
        'This is optional, press <info>enter</info> to <info>continue</info>',
        ''
      ]);

      $services = $this->getServices();
      while (true) {
        $service = $dialog->askAndValidate(
          $output,
          $dialog->getQuestion(' Enter your service',''),
          function ($service) use ($services) {
            return $this->validateServiceExist($service, $services);
          },
          false,
          null,
          $services
        );

        if ($service == null) {
          break;
        }

        array_push($service_collection, $service);
        $service_key = array_search($service, $services, true);

        if ($service_key >= 0)
          unset($services[$service_key]);
      }

      return $service_collection;
    }

    return null;
  }

  /**
   * @param  Array $services
   * @return Array
   */
  public function buildServices($services)
  {
    if (!empty($services)) {
      $build_service = [];
      foreach ($services as $service) {
        $class = get_class($this->getContainer()->get($service));
        $explode_class = explode('\\', $class);
        $build_service[$service] = [
          'name' => $service,
          'machine_name' => str_replace('.', '_', $service),
          'class' => $class,
          'short' => end($explode_class),
          ];
      }
      return $build_service;
    }
    return null;
  }
}
