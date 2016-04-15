<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\EnableCommand.
 */

namespace Drupal\Console\Command\Views;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class EnableCommand
 * @package Drupal\Console\Command\Views
 */
class EnableCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $viewId = $input->getArgument('view-id');

        $entityManager = $this->getEntityManager();
        $view = $entityManager->getStorage('view')->load($viewId);

        if (empty($view)) {
            $io->error(
                sprintf(
                    $this->trans('commands.views.debug.messages.not-found'),
                    $viewId
                )
            );
            return;
        }

        try {
            $view->enable()->save();
            $io->success(
                sprintf(
                    $this->trans('commands.views.enable.messages.enabled-successfully'),
                    $view->get('label')
                )
            );
        } catch (Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
