<?php

/**
 * @file
 * Contains Drupal\Console\Command\GenerateEntityBundleCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Generator\ContentTypeGenerator;
use Drupal\Console\Generator\EntityBundleGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEntityBundleCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:entity:bundle')
            ->setDescription($this->trans('commands.generate.entity.bundle.description'))
            ->setHelp($this->trans('commands.generate.entity.bundle.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'bundle-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.entity.bundle.options.bundle-name')
            )
            ->addOption(
                'bundle-title',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.entity.bundle.options.bundle-title')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $bundleName = $input->getOption('bundle-name');
        $bundleTitle = $input->getOption('bundle-title');

        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $bundleName, $bundleTitle);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --bundle-name option
        $bundle_name = $input->getOption('bundle-name');
        if (!$bundle_name) {
            $bundleName = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.entity.bundle.questions.bundle-name'), 'default'),
                function ($bundle_name) {
                    return $this->validateClassName($bundle_name);
                },
                false,
                'default',
                null
            );
        }
        $input->setOption('bundle-name', $bundleName);

        // --bundle-title option
        $bundleTitle = $input->getOption('bundle-title');
        if (!$bundleTitle) {
            $bundleTitle = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.entity.bundle.questions.bundle-title'), 'default'),
                function ($bundle_title) {
                    return $this->getValidator()->validateBundleTitle($bundle_title);
                },
                false,
                'default',
                null
            );
        }
        $input->setOption('bundle-title', $bundleTitle);
    }

    /**
     * @return \Drupal\Console\Generator\EntityBundleGenerator
     */
    protected function createGenerator()
    {
        return new EntityBundleGenerator();
    }
}
