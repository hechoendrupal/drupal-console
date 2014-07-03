<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ContainerDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerDebugCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('container:debug')
      ->setDescription('Displays current services for an application')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // skip services
    // service declaration at core/core.services.yml file, arguments are not properly set, must be
    // arguments: ['@controller_resolver', '@entity.manager', '@form_builder', NULL]
    $skip[] = 'controller.entityform';
    // scope: request declaration make it fails on container->get($id)
    // find a better way to retrieve services filtering by tag or scope
    $skip = array_merge($skip, ['finish_response_subscriber', 'redirect_response_subscriber']);

    $services = $this->getServices();
    $table = $this->getHelperSet()->get('table');
    $table->setHeaders(['Service ID', 'Class name']);
    $table->setlayout($table::LAYOUT_COMPACT);
    foreach ($services as $serviceId) {
      if ( false === array_search($serviceId, $skip) ) {
        $service = $this->getContainer()->get($serviceId);
        $class = get_class($service);
        $table->addRow([$serviceId, $class]);
      }
    }
    $table->render($output);
  }
}
