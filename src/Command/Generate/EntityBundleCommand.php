<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\EntityBundleCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ServicesTrait;
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
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $bundleName = $input->getOption('bundle-name');
        $bundleTitle = $input->getOption('bundle-title');
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $bundleName, $bundleTitle);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --bundle-name option
        $bundleName = $input->getOption('bundle-name');
        if (!$bundleName) {
            $bundleName = $io->ask(
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
            $bundleTitle = $io->ask(
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
