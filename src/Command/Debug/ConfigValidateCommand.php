<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ConfigValidateCommand.
 */

namespace Drupal\Console\Command\Debug;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Console\Command\Config\PrintConfigValidationTrait;

/**
 * Class ConfigValidateCommand.
 *
 *@package Drupal\Console\Command\Debug
 */
class ConfigValidateCommand extends ContainerAwareCommand
{
    use SchemaCheckTrait;
    use PrintConfigValidationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:config:validate')
            ->setDescription($this->trans('commands.debug.config.validate.description'))
            ->addArgument(
            		'filepath',
            		InputArgument::REQUIRED
            )
            ->addArgument(
            		'schema-filepath',
            		InputArgument::REQUIRED
            )
            ->addOption(
            		'schema-name',
            		'sch',
            		InputOption::VALUE_REQUIRED
            )->setAliases(['dcv']);
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

        //Validate config file path
        $configFilePath = $input->getArgument('filepath');
        if (!file_exists($configFilePath)) {
            $io->info($this->trans('commands.debug.config.validate.messages.noConfFile'));
            return 1;
        }

        //Validate schema path
        $configSchemaFilePath = $input->getArgument('schema-filepath');
        if (!file_exists($configSchemaFilePath)) {
            $io->info($this->trans('commands.debug.config.validate.messages.noConfSchema'));
            return 1;
        }

        $config = Yaml::decode(file_get_contents($configFilePath));
        $schema = Yaml::decode(file_get_contents($configSchemaFilePath));

        //Get the schema name and check it exists in the schema array
        $schemaName = $this->getSchemaName($input, $configFilePath);
        if (!array_key_exists($schemaName, $schema)) {
            $io->warning($this->trans('commands.debug.config.validate.messages.noSchemaName') . $schemaName);
            return 1;
        }

        return $this->printResults($this->manualCheckConfigSchema($typedConfigManager, $config, $schema[$schemaName]), $io);
    }

    private function getSchemaName(InputInterface $input, $configFilePath)
    {
        $schemaName = $input->getOption('schema-name');
        if ($schemaName === null) {
            $schema_name = end(explode('/', $configFilePath));
            $schemaName = substr($schema_name, 0, -4);
        }
        return $schemaName;
    }

    private function manualCheckConfigSchema(TypedConfigManagerInterface $typed_config, $config_data, $config_schema)
    {
        $data_definition = $typed_config->buildDataDefinition($config_schema, $config_data);
        $this->schema = $typed_config->create($data_definition, $config_data);
        $errors = [];
        foreach ($config_data as $key => $value) {
            $errors = array_merge($errors, $this->checkValue($key, $value));
        }
        if (empty($errors)) {
            return true;
        }

        return $errors;
    }
}
