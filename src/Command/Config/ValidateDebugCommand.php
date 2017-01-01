<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ValidateDebugCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Class ValidateDebugCommand.
 *
 *@package Drupal\Console\Command\Config
 */
class ValidateDebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    use SchemaCheckTrait;
    use PrintConfigValidationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:validate:debug')
            ->setDescription($this->trans('commands.config.validate.debug.description'))
            ->addArgument('config.filepath', InputArgument::REQUIRED)
            ->addArgument('config.schema.filepath', InputArgument::REQUIRED)
            ->addOption('schema-name', 'sch', InputOption::VALUE_REQUIRED);
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
        $configFilePath = $input->getArgument('config.filepath');
        if (!file_exists($configFilePath)) {
            $io->info($this->trans('commands.config.validate.debug.messages.noConfFile'));
            return 1;
        }

        //Validate schema path
        $configSchemaFilePath = $input->getArgument('config.schema.filepath');
        if (!file_exists($configSchemaFilePath)) {
            $io->info($this->trans('commands.config.validate.debug.messages.noConfSchema'));
            return 1;
        }

        $config = Yaml::decode(file_get_contents($configFilePath));
        $schema = Yaml::decode(file_get_contents($configSchemaFilePath));

        //Get the schema name and check it exists in the schema array
        $schemaName = $this->getSchemaName($input, $configFilePath);
        if (!array_key_exists($schemaName, $schema)) {
            $io->warning($this->trans('commands.config.validate.debug.messages.noSchemaName') . $schemaName);
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
