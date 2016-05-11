<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DeleteCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class DeleteCommand extends BaseCommand
{
    use ContainerAwareCommandTrait;
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
            $configFactory = $this->getDrupalService('config.factory');
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
        $configFactory = $this->getDrupalService('config.factory');
        $name = $input->getArgument('name');
        if (!$name) {
            $io->error($this->trans('commands.config.delete.messages.name'));

            return 1;
        }

        $configStorage = $this->getDrupalService('config.storage');
        if (!$configStorage->exists($name)) {
            $io->error(
                sprintf(
                    $this->trans('commands.config.delete.messages.not-exists'),
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
