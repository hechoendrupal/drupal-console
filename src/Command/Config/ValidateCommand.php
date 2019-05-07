<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ValidateCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Core\Config\Schema\SchemaCheckTrait;

/**
 * Class ValidateCommand.
 *
 * @package Drupal\Console\Command\Config
 */
class ValidateCommand extends ContainerAwareCommand
{
    use SchemaCheckTrait;
    use PrintConfigValidationTrait;

    /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
            ->setName('config:validate')
            ->setDescription($this->trans('commands.config.validate.description'))
            ->addArgument(
                'name',
                InputArgument::REQUIRED
            )->setAliases(['cv']);
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var TypedConfigManagerInterface $typedConfigManager
         */
        $typedConfigManager = $this->get('config.typed');

        //Test the config name and see if a schema exists, if not it will fail
        $name = $input->getArgument('name');
        if (!$typedConfigManager->hasConfigSchema($name)) {
            $this->getIo()->warning($this->trans('commands.config.validate.messages.no-conf'));
            return 1;
        }

        //Get the config data from the factory
        $configFactory = $this->get('config.factory');
        $config_data = $configFactory->get($name)->get();

        return $this->printResults($this->checkConfigSchema($typedConfigManager, $name, $config_data));
    }
}
