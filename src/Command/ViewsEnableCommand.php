<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Herrera\Json\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\views\Entity\View;
use Drupal\Component\Serialization\Yaml;

class ViewsEnableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('views:enable')
            ->setDescription($this->trans('commands.views.enable.description'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-id')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $view_id = $input->getArgument('view-id');

        $entity_manager = $this->getEntityManager();
        $view = $entity_manager->getStorage('view')->load($view_id);

        if (empty($view)) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.views.debug.messages.not-found'),
                    $view_id
                ).'</error>'
            );
            return;
        }

        try {
            $view->enable()->save();

            $output->writeln(
                '[-] <info>'. sprintf($this->trans('commands.views.enable.messages.disabled-successfully'), $view->get('label')) . '</info>'
            );
        } catch (Exception $e) {
            $output->writeln(
                '[+] <error>'. $e->getMessage() . '</error>'
            );
        }
    }
}
