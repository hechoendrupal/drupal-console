<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\FormAlterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\FormAlterGenerator;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\MenuTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class FormAlterCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use MenuTrait;
    use ConfirmationTrait;

    protected $metadata = ['unset' => []];

    protected function configure()
    {
        $this
            ->setName('generate:form:alter')
            ->setDescription($this->trans('commands.generate.form.alter.description'))
            ->setHelp($this->trans('commands.generate.form.alter.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'form-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.alter.options.form-id')
            )
            ->addOption(
                'inputs',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $formId = $input->getOption('form-id');
        $inputs = $input->getOption('inputs');

        $this
            ->getGenerator()
            ->generate($module, $formId, $inputs, $this->metadata);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $moduleHandler = $this->getModuleHandler();
        $drupal = $this->getDrupalHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
        }
        $input->setOption('module', $module);

        // --form-id option
        $formId = $input->getOption('form-id');
        if (!$formId) {
            $forms = [];
            // Get form ids from webprofiler
            if ($moduleHandler->moduleExists('webprofiler')) {
                $io->info(
                    $this->trans('commands.generate.form.alter.messages.loading-forms')
                );
                $forms = $this->getWebprofilerForms();
            }

            if (!empty($forms)) {
                $formId = $io->choiceNoList(
                    $this->trans('commands.generate.form.alter.options.form-id'),
                    array_keys($forms)
                );
            }
        }

        if ($moduleHandler->moduleExists('webprofiler') && isset($forms[$formId])) {
            $this->metadata['class'] = $forms[$formId]['class']['class'];
            $this->metadata['method'] = $forms[$formId]['class']['method'];
            $this->metadata['file'] = str_replace($drupal->getRoot(), '', $forms[$formId]['class']['file']);

            $formItems = array_keys($forms[$formId]['form']);

            $formItemsToHide = $io->choice(
                $this->trans('commands.generate.form.alter.messages.hide-form-elements'),
                $formItems,
                null,
                true
            );

            $this->metadata['unset'] = array_filter(array_map('trim', explode(',', $formItemsToHide)));
        }

        $input->setOption('form-id', $formId);

        $io->writeln($this->trans('commands.generate.form.alter.messages.inputs'));

        // @see Drupal\Console\Command\FormTrait::formQuestion
        $form = $this->formQuestion($io);
        $input->setOption('inputs', $form);
    }

    protected function createGenerator()
    {
        return new FormAlterGenerator();
    }
}
