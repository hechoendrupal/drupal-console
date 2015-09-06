<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorControllerCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\Helper\ConfirmationTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Generator\ContentTypeGenerator;
use Drupal\AppConsole\Generator\ControllerGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorContentTypeCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:contenttype')
            ->setDescription($this->trans('commands.generate.contenttype.description'))
            ->setHelp($this->trans('commands.generate.contenttype.command.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'bundle-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.contenttype.options.bundle-name')
            )
            ->addOption(
                'bundle-title',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.contenttype.options.bundle-title')
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
        $bundle_name = $input->getOption('bundle-name');
        $bundle_title = $input->getOption('bundle-title');

        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $bundle_name, $bundle_title);

        // Consider chaining the import command
        //$this->getHelper('chain')->addCommand('router:rebuild');
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
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --bundle-name option
        $bundle_name = $input->getOption('bundle-name');
        if (!$bundle_name) {
            $bundle_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.contenttype.questions.bundle-name'), 'default'),
                function ($bundle_name) {
                    return $this->validateClassName($bundle_name);
                },
                false,
                'default',
                null
            );
        }
        $input->setOption('bundle-name', $bundle_name);

        // --bundle-title option
        $bundle_title = $input->getOption('bundle-title');
        if (!$bundle_title) {
            $bundle_title = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.contenttype.questions.bundle-title'), 'default'),
                function ($bundle_title) {
                    return $this->validateClassName($bundle_title);
                },
                false,
                'default',
                null
            );
        }
        $input->setOption('bundle-title', $bundle_title);
    }

    /**
     * @return \Drupal\AppConsole\Generator\ControllerGenerator
     */
    protected function createGenerator()
    {
        return new ContentTypeGenerator();
    }
}
