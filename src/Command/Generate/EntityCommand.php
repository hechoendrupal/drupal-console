<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ModuleTrait;

abstract class EntityCommand extends Command
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
                'base-path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.' . $commandKey . '.options.base-path')
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
        $commandKey = str_replace(':', '.', $this->commandName);
        $utils = $this->stringConverter;

        // --module option
        $this->getModuleOption();

        // --entity-class option
        $entityClass = $input->getOption('entity-class');
        if (!$entityClass) {
            $entityClass = $this->getIo()->ask(
                $this->trans('commands.'.$commandKey.'.questions.entity-class'),
                'DefaultEntity',
                function ($entityClass) {
                    return $this->validator->validateSpaces($entityClass);
                }
            );

            $input->setOption('entity-class', $entityClass);
        }

        // --entity-name option
        $entityName = $input->getOption('entity-name');
        if (!$entityName) {
            $entityName = $this->getIo()->ask(
                $this->trans('commands.'.$commandKey.'.questions.entity-name'),
                $utils->camelCaseToMachineName($entityClass),
                function ($entityName) {
                    return $this->validator->validateMachineName($entityName);
                }
            );
            $input->setOption('entity-name', $entityName);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
                $this->trans('commands.'.$commandKey.'.questions.label'),
                $utils->camelCaseToHuman($entityClass)
            );
            $input->setOption('label', $label);
        }

        // --base-path option
        $base_path = $input->getOption('base-path');
        if (!$base_path) {
            $base_path = $this->getDefaultBasePath();
        }
        $base_path = $this->getIo()->ask(
            $this->trans('commands.'.$commandKey.'.questions.base-path'),
            $base_path
        );
        if (substr($base_path, 0, 1) !== '/') {
            // Base path must start with a leading '/'.
            $base_path = '/' . $base_path;
        }
        $input->setOption('base-path', $base_path);
    }

    /**
     * Gets default base path.
     *
     * @return string
     *   Default base path.
     */
    protected function getDefaultBasePath()
    {
        return '/admin/structure';
    }
}
