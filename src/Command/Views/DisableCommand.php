<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\DisableCommand.
 */

namespace Drupal\Console\Command\Views;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DisableCommand
 * @package Drupal\Console\Command\Views
 */
class DisableCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('views:disable')
            ->setDescription($this->trans('commands.views.disable.description'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-id')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $views = $this->getDrupalService('entity.query')
                ->get('view')
                ->condition('status', 1)
                ->execute();
            $viewId = $io->choiceNoList(
                $this->trans('commands.views.debug.arguments.view-id'),
                $views
            );
            $input->setArgument('view-id', $viewId);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $viewId = $input->getArgument('view-id');

        $entityTypeManager =  $this->getDrupalService('entity_type.manager');

        $view = $entityTypeManager->getStorage('view')->load($viewId);

        if (empty($view)) {
            $io->error(sprintf($this->trans('commands.views.debug.messages.not-found'), $viewId));
            return;
        }

        try {
            $view->disable()->save();

            $io->success(sprintf($this->trans('commands.views.disable.messages.disabled-successfully'), $view->get('label')));
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
