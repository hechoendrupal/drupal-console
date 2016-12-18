<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ValidateCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Core\Config\Schema\SchemaCheckTrait;

/**
 * Class ValidateCommand.
 *
 * @package Drupal\Console\Command\Config
 */
class ValidateCommand extends Command
{
    use ContainerAwareCommandTrait;
    use SchemaCheckTrait;
    use PrintConfigValidationTrait;

    public function __construct($name)
    {
        parent::__construct($name);
    }


    /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
            ->setName('config:validate')
            ->setDescription($this->trans('commands.config.default.description'))
            ->addArgument('config.name', InputArgument::REQUIRED);
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

        $io = new DrupalStyle($input, $output);

        //Test the config name and see if a schema exists, if not it will fail
        $name = $input->getArgument('config.name');
        if (!$typedConfigManager->hasConfigSchema($name)) {
            $io->warning($this->trans('commands.config.default.messages.noconf'));
            return 1;
        }

        //Get the config data from the factory
        $configFactory = $this->get('config.factory');
        $config_data = $configFactory->get($name)->get();

        return $this->printResults($this->checkConfigSchema($typedConfigManager, $name, $config_data), $io);
    }
}
