<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\EntityBundleCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Generator\ContentTypeGenerator;
use Drupal\Console\Generator\EntityBundleGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

class EntityBundleCommand extends GeneratorCommand
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
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
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
        $output = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --bundle-name option
        $bundleName = $input->getOption('bundle-name');
        if (!$bundleName) {
            $bundleName = $output->ask(
                $this->trans('commands.generate.entity.bundle.questions.bundle-name'),
                'default',
                function ($bundleName) {
                    return $this->validateClassName($bundleName);
                }
            );
            $input->setOption('bundle-name', $bundleName);
        }

        // --bundle-title option
        $bundleTitle = $input->getOption('bundle-title');
        if (!$bundleTitle) {
            $bundleTitle = $output->ask(
                $this->trans('commands.generate.entity.bundle.questions.bundle-title'),
                'default',
                function ($bundle_title) {
                    return $this->getValidator()->validateBundleTitle($bundle_title);
                }
            );
            $input->setOption('bundle-title', $bundleTitle);
        }
    }

    /**
     * @return \Drupal\Console\Generator\EntityBundleGenerator
     */
    protected function createGenerator()
    {
        return new EntityBundleGenerator();
    }
}
