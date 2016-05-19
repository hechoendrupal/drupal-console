<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DeleteCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Core\Config\FileStorage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Yaml\Exception\RuntimeException;

class DeleteCommand extends ContainerAwareCommand
{
    protected $allConfig = [];
    protected $configFactory = null;

    /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
            ->setName('config:delete')
            ->setDescription($this->trans('commands.config.delete.description'))
            ->addArgument('type', InputArgument::OPTIONAL, $this->trans('commands.config.delete.arguments.type'))
            ->addArgument('name', InputArgument::OPTIONAL, $this->trans('commands.config.delete.arguments.name'));
    }

    /**
   * {@inheritdoc}
   */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // Init Drupal style and retrieve name argument.
        $io = new DrupalStyle($input, $output);
        // Check config type is not missing.
        $type = $input->getArgument('type');
        if (!$type) {
            // Define choice list to configuration type.
            $type = $io->choiceNoList(
                $this->trans('commands.config.delete.arguments.type'),
                ['active', 'staging'],
                'active'
            );
            $input->setArgument('type', $type);
        }

        // Check config name is not missing.
        $name = $input->getArgument('name');
        if (!$name) {
            // Define choice list to configuration name.
            $name = $io->choiceNoList(
                $this->trans('commands.config.delete.arguments.name'),
                $this->getAllConfigNames(),
                'all'
            );
            $input->setArgument('name', $name);
        }
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Init Drupal style and retrieve name argument.
        $io = new DrupalStyle($input, $output);
        // Check config type is not missing.
        $type = $input->getArgument('type');
        if (!$type) {
            $io->error($this->trans('commands.config.delete.errors.type'));
            return 1;
        }

        // Check config name is not missing.
        $name = $input->getArgument('name');
        if (!$name) {
            $io->error($this->trans('commands.config.delete.errors.name'));
            return 1;
        }

        // Define Configuration Storage.
        $configStorage = ('active' === $type) ?
        $this->getService('config.storage') :
        \Drupal::service('config.storage.sync');
        if (!$configStorage) {
            $io->error($this->trans('commands.config.delete.errors.config-storage'));
            return 1;
        }

        // Check if current option chose was "all".
        if ('all' === $name) {
            // Caveat about remove all configuration.
            $io->caution($this->trans('commands.config.delete.warnings.undo'));
            // Double check before execute it.
            if ($io->confirm($this->trans('commands.config.delete.questions.sure'))) {

                // Check configStorage instance of.
                if ($configStorage instanceof FileStorage) {
                    // Delete YAML file.
                    $configStorage->deleteAll();
                } else {
                    // Remove all configuration.
                    foreach ($this->yieldAllConfig() as $name) {
                        $this->removeConfig($name);
                    }
                }

                // Define successful message.
                $io->success($this->trans('commands.config.delete.messages.all'));
            }
        } // Load $configStorage and check config name already exists.
        elseif ($configStorage->exists($name)) {

            // Check configStorage instance of.
            if ($configStorage instanceof FileStorage) {
                // Delete YAML file.
                $configStorage->delete($name);
            } else {
                // Remove given configuration.
                $this->removeConfig($name);
            }

            // Define and print successful message.
            $message = sprintf($this->trans('commands.config.delete.messages.deleted'), $name);
            $io->success($message);
        } else {
            // Otherwise, shows up error because config name does not exist.
            $message = sprintf($this->trans('commands.config.delete.errors.not-exists'), $name);
            $io->error($message);
            return 1;
        }
    }

    /**
   * Retrieve config factory property.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface|null
   */
    private function configFactory()
    {
        // Define config factory from service if it does not exist.
        $this->configFactory = $this->configFactory ?: $this->getConfigFactory();
        return $this->configFactory;
    }

    /**
   * Retrieve configuration names form cache or service factory.
   *
   * @return array
   *   All configuration names.
   */
    private function getAllConfigNames()
    {
        // If configuration names exist, then return them.
        if (!empty($this->allConfig)) {
            return $this->allConfig;
        }
        // Retrieve configuration factory.
        foreach ($this->configFactory()->listAll() as $name) {
            // Store configuration name.
            $this->allConfig[] = $name;
        }
        // Return all configuration names.
        return $this->allConfig;
    }

    /**
   * Yield configuration names.
   *
   * @return \Generator
   *   Yield generator with config name.
   */
    private function yieldAllConfig()
    {
        // Be sure $allConfig property already exists.
        $this->allConfig = $this->allConfig ?: $this->getAllConfigNames();
        // Walk trough all config names and yield them.
        foreach ($this->allConfig as $name) {
            yield $name;
        }
    }

    /**
   * Delete given config name.
   *
   * @param String $name Given config name.
   */
    private function removeConfig($name)
    {
        try {
            // Retrieve config factory and delete given configuration.
            $this->configFactory()->getEditable($name)->delete();
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
