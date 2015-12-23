<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\EnableCommand.
 */

namespace Drupal\Console\Command\Views;

use Herrera\Json\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class EnableCommand extends ContainerAwareCommand
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
        $io = new DrupalStyle($input, $output);
        $view_id = $input->getArgument('view-id');

        $entity_manager = $this->getEntityManager();
        $view = $entity_manager->getStorage('view')->load($view_id);

        if (empty($view)) {
            $io->error(sprintf($this->trans('commands.views.debug.messages.not-found'), $view_id));
            return;
        }

        try {
            $view->enable()->save();
            $io->info(sprintf($this->trans('commands.views.enable.messages.disabled-successfully'), $view->get('label')));
        } catch (Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
