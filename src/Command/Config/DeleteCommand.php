<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DeleteCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class DeleteCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:delete')
            ->setDescription($this->trans('commands.config.delete.description'))
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.delete.arguments.name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $name = $input->getArgument('name');
        if (!$name) {
            $configFactory = $this->getService('config.factory');
            $names = $configFactory->listAll();
            $name = $io->choiceNoList(
                $this->trans('commands.config.delete.arguments.name'),
                $names
            );
            $input->setArgument('name', $name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $configFactory = $this->getService('config.factory');
        $name = $input->getArgument('name');
        if (!$name) {
            $io->error($this->trans('commands.config.delete.messages.enter-name'));

            return 1;
        }

        $configStorage = $this->getService('config.storage');
        if (!$configStorage->exists($name)) {
            $io->error(
                sprintf(
                    $this->trans('commands.config.delete.messages.config-not-exists'),
                    $name
                )
            );

            return 1;
        }

        try {
            $configFactory->getEditable($name)->delete();
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.config.delete.messages.deleted'),
                $name
            )
        );
    }
}
