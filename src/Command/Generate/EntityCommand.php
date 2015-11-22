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
use Drupal\Console\Generator\EntityConfigGenerator;
use Drupal\Console\Generator\EntityContentGenerator;
use Drupal\Console\Command\GeneratorCommand;

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
      if ('EntityContent' == $this->entityType) {
        $this
            ->setName($this->commandName)
            ->setDescription(
                sprintf(
                    $this->trans('commands.generate.entity.content.description'),
                    $this->entityType
                )
            )
            ->setHelp(
                sprintf(
                    $this->trans('commands.generate.entity.content.help'),
                    $this->commandName,
                    $this->entityType
                )
            )
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'entity-class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.entity.content.options.entity-class')
            )
            ->addOption(
                'entity-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.entity.content.options.entity-name')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.entity.content.options.label')
            );
      }
      else {
        $this
            ->setName($this->commandName)
            ->setDescription(
                sprintf(
                    $this->trans('commands.generate.entity.config.description'),
                    $this->entityType
                )
            )
            ->setHelp(
                sprintf(
                    $this->trans('commands.generate.entity.config.help'),
                    $this->commandName,
                    $this->entityType
                )
            )
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'entity-class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.entity.config.options.entity-class')
            )
            ->addOption(
                'entity-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.entity.config.options.entity-name')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.entity.config.options.label')
            );
      }

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityType = $this->getStringHelper()->camelCaseToUnderscore($this->entityType);

        $module = $input->getOption('module');
        $entity_class = $input->getOption('entity-class');
        $entity_name = $input->getOption('entity-name');
        $label = $input->getOption('label');

        $this
            ->getGenerator()
            ->generate($module, $entity_name, $entity_class, $label, $entityType);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $utils = $this->getStringHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --entity-class option
        $entity_class = $input->getOption('entity-class');
        if (!$entity_class) {
            $entity_class = 'DefaultEntity';
            $entity_class = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.entity.questions.entity-class'), $entity_class),
                function ($entity_class) {
                    return $this->validateSpaces($entity_class);
                },
                false,
                $entity_class,
                null
            );
        }
        $input->setOption('entity-class', $entity_class);

        $machine_name = $utils->camelCaseToMachineName($entity_class);

        // --entity-name option
        $entity_name = $input->getOption('entity-name');
        if (!$entity_name) {
            $entity_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.entity.questions.entity-name'), $machine_name),
                function ($machine_name) {
                    return $this->validateMachineName($machine_name);
                },
                false,
                $machine_name,
                null
            );
        }
        $input->setOption('entity-name', $entity_name);

        $default_label = $utils->camelCaseToHuman($entity_class);

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.entity.questions.label'), $default_label),
                $default_label
            );
        }
        $input->setOption('label', $label);
    }

    protected function createGenerator()
    {
        if ('EntityContent' == $this->entityType) {
            return new EntityContentGenerator();
        }

        return new EntityConfigGenerator();
    }
}
