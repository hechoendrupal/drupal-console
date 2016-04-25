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
            ->addOption(
                'name',
                $this->trans('commands.config.delete.arguments.name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
          $name = $input->getOption('name');
        if (!$name) {
            $configFactory = $this->getConfigFactory();
            $names = $configFactory->listAll();
            $name = $io->choiceNoList(
                $this->trans('commands.config.delete.arguments.name'),
                $names
            );
            $input->setOption('name', $name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $configFactory = $this->getConfigFactory();
        $name = $input->getOption('name');
        if (!$name) {
            $io->error($this->trans('commands.config.delete.messages.name'));

            return 1;
        }

        $configStorage = $this->getConfigStorage();
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
