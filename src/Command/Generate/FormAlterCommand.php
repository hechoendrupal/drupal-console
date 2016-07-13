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
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\MenuTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class FormAlterCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use MenuTrait;
    use ConfirmationTrait;

    protected $metadata = ['class' => [],'method'=> [],'file'=> [],'unset' => []];

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

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $formId = $input->getOption('form-id');
        $inputs = $input->getOption('inputs');

        $function = $module . '_form_' .$formId . '_alter';
        
        if ($this->validateModuleFunctionExist($module, $function)) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.form.alter.messages.help-already-implemented'),
                    $module
                )
            );
        }

        //validate if input is an array
        if (!is_array($inputs[0])) {
            $inputs= $this->explodeInlineArray($inputs);
        }

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
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
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

            foreach ($forms[$formId]['form'] as $itemKey => $item) {
                if ($item['#type'] == 'hidden') {
                    unset($forms[$formId]['form'][$itemKey]);
                }
            }

            unset($forms[$formId]['form']['form_build_id']);
            unset($forms[$formId]['form']['form_token']);
            unset($forms[$formId]['form']['form_id']);
            unset($forms[$formId]['form']['actions']);

            $formItems = array_keys($forms[$formId]['form']);

            $formItemsToHide = $io->choice(
                $this->trans('commands.generate.form.alter.messages.hide-form-elements'),
                $formItems,
                null,
                true
            );

            $this->metadata['unset'] = array_filter(array_map('trim',  $formItemsToHide));
        }

        $input->setOption('form-id', $formId);

        // @see Drupal\Console\Command\Shared\FormTrait::formQuestion
        $inputs = $input->getOption('inputs');

        if (empty($inputs)) {
            $io->writeln($this->trans('commands.generate.form.alter.messages.inputs'));
            $inputs = $this->formQuestion($io);
        } else {
            $inputs= $this->explodeInlineArray($inputs);
        }

        $input->setOption('inputs', $inputs);
    }

    /**
     * @{@inheritdoc}
     */
    public function explodeInlineArray($inlineInputs)
    {
        $inputs = [];
        foreach ($inlineInputs as $inlineInput) {
            $explodeInput = explode(" ", $inlineInput);
            $parameters = [];
            foreach ($explodeInput as $inlineParameter) {
                list($key, $value) = explode(":", $inlineParameter);
                if (!empty($value)) {
                    $parameters[$key] = $value;
                }
            }
            $inputs[] = $parameters;
        }

        return $inputs;
    }

    protected function createGenerator()
    {
        return new FormAlterGenerator();
    }
}
