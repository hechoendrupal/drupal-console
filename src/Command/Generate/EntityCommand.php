<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

abstract class EntityCommand extends GeneratorCommand
{
    use ModuleTrait;
    private $entityType;
    private $commandName;

    /**
     * @param $entityType
     */
    protected function setEntityType($entityType)
    {
        $this->entityType = $entityType;
    }

    /**
     * @param $commandName
     */
    protected function setCommandName($commandName)
    {
        $this->commandName = $commandName;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $commandKey = str_replace(':', '.', $this->commandName);

        $this
            ->setName($this->commandName)
            ->setDescription(
                sprintf(
                    $this->trans('commands.'.$commandKey.'.description'),
                    $this->entityType
                )
            )
            ->setHelp(
                sprintf(
                    $this->trans('commands.'.$commandKey.'.help'),
                    $this->commandName,
                    $this->entityType
                )
            )
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'entity-class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.'.$commandKey.'.options.entity-class')
            )
            ->addOption(
                'entity-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.'.$commandKey.'.options.entity-name')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.'.$commandKey.'.options.label')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Operations defined in EntityConfigCommand and EntityContentCommand.
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $commandKey = str_replace(':', '.', $this->commandName);
        $utils = $this->getStringHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --entity-class option
        $entityClass = $input->getOption('entity-class');
        if (!$entityClass) {
            $entityClass = $output->ask(
                $this->trans('commands.'.$commandKey.'.questions.entity-class'),
                'DefaultEntity',
                function ($entityClass) {
                    return $this->validateSpaces($entityClass);
                }
            );

            $input->setOption('entity-class', $entityClass);
        }

        // --entity-name option
        $entityName = $input->getOption('entity-name');
        if (!$entityName) {
            $entityName = $output->ask(
                $this->trans('commands.'.$commandKey.'.questions.entity-name'),
                $utils->camelCaseToMachineName($entityClass),
                function ($entityName) {
                    return $this->validateMachineName($entityName);
                }
            );
            $input->setOption('entity-name', $entityName);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $output->ask(
                $this->trans('commands.'.$commandKey.'.questions.label'),
                $utils->camelCaseToHuman($entityClass)
            );
            $input->setOption('label', $label);
        }
    }

    protected function createGenerator()
    {
    }
}
