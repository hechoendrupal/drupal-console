<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\UpdateCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\UpdateGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class UpdateCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:update')
            ->setDescription($this->trans('commands.generate.update.description'))
            ->setHelp($this->trans('commands.generate.update.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'update-n',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.update.options.update-n')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $updateNumber = $input->getOption('update-n');

        $lastUpdateSchema = $this->getLastUpdate($module);

        if ($updateNumber <= $lastUpdateSchema) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.generate.update.messages.wrong-update-n'),
                    $updateNumber
                )
            );
        }

        $this
            ->getGenerator()
            ->generate($module, $updateNumber);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->getDrupalHelper()->loadLegacyFile('/core/includes/update.inc');
        $this->getDrupalHelper()->loadLegacyFile('/core/includes/schema.inc');

        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        $lastUpdateSchema = $this->getLastUpdate($module);
        $nextUpdateSchema = $lastUpdateSchema ? ($lastUpdateSchema + 1): 8001;

        $updateNumber = $input->getOption('update-n');
        if (!$updateNumber) {
            $updateNumber = $io->ask(
                $this->trans('commands.generate.update.questions.update-n'),
                $nextUpdateSchema,
                function ($updateNumber) use ($lastUpdateSchema) {
                    if (!is_numeric($updateNumber)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.update.messages.wrong-update-n'),
                                $updateNumber
                            )
                        );
                    } else {
                        if ($updateNumber <= $lastUpdateSchema) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    $this->trans('commands.generate.update.messages.wrong-update-n'),
                                    $updateNumber
                                )
                            );
                        }
                        return $updateNumber;
                    }
                }
            );

            $input->setOption('update-n', $updateNumber);
        }
    }


    protected function createGenerator()
    {
        return new UpdateGenerator();
    }

    protected function getLastUpdate($module)
    {
        $this->getDrupalHelper()->loadLegacyFile('/core/includes/update.inc');
        $this->getDrupalHelper()->loadLegacyFile('/core/includes/schema.inc');

        $updates = update_get_update_list();

        if (empty($updates[$module]['pending'])) {
            $lastUpdateSchema = drupal_get_schema_versions($module);
            $lastUpdateSchema = $lastUpdateSchema[0];
        } else {
            $lastUpdateSchema = reset(array_keys($updates[$module]['pending'], max($updates[$module]['pending'])));
        }

        return $lastUpdateSchema;
    }
}
