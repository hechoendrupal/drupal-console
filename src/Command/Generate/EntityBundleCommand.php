<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\EntityBundleCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Generator\EntityBundleGenerator;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;

class EntityBundleCommand extends Command
{
    use ModuleTrait;
    use ServicesTrait;
    use ConfirmationTrait;

    /**
     * @var Validator
     */
    protected $validator;

    /**
 * @var EntityBundleGenerator
*/
    protected $generator;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
     * EntityBundleCommand constructor.
     *
     * @param Validator             $validator
     * @param EntityBundleGenerator $generator
     * @param Manager               $extensionManager
     */
    public function __construct(
        Validator $validator,
        EntityBundleGenerator $generator,
        Manager $extensionManager
    ) {
        $this->validator = $validator;
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('generate:entity:bundle')
            ->setDescription($this->trans('commands.generate.entity.bundle.description'))
            ->setHelp($this->trans('commands.generate.entity.bundle.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'bundle-name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.entity.bundle.options.bundle-name')
            )
            ->addOption(
                'bundle-title',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.entity.bundle.options.bundle-title')
            )
            ->setAliases(['geb']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $module = $input->getOption('module');
        $bundleName = $input->getOption('bundle-name');
        $bundleTitle = $input->getOption('bundle-title');

        $generator = $this->generator;
        //TODO:
        //        $generator->setLearning($learning);
        $generator->generate($module, $bundleName, $bundleTitle);

        return 0;
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
                    return $this->validator->validateClassName($bundleName);
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
                    return $this->validator->validateBundleTitle($bundle_title);
                }
            );
            $input->setOption('bundle-title', $bundleTitle);
        }
    }
}
