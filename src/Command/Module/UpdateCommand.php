<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UpdateCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Core\Utils\ShellProcess;

class UpdateCommand extends Command
{
    use ProjectDownloadTrait;


    /**
 * @var ShellProcess
*/
    protected $shellProcess;

    /**
     * @var string
     */
    protected $root;

    /**
     * UpdateCommand constructor.
     *
     * @param ShellProcess $shellProcess
     * @param $root
     */
    public function __construct(
        ShellProcess $shellProcess,
        $root
    ) {
        $this->shellProcess = $shellProcess;
        $this->root = $root;
        parent::__construct();
    }
    protected function configure()
    {
        $this
            ->setName('module:update')
            ->setDescription($this->trans('commands.module.update.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.update.arguments.module')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.update.options.composer')
            )
            ->addOption(
                'simulate',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.update.options.simulate')
            )->setAliases(['moup']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $composer = $input->getOption('composer');
        $module = $input->getArgument('module');

        if (!$composer) {
            $io->error($this->trans('commands.module.update.messages.only-composer'));

            return 1;
        }

        if (!$module) {
            $module = $this->modulesQuestion($io);
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $modules = $input->getArgument('module');
        $composer = $input->getOption('composer');
        $simulate = $input->getOption('simulate');

        if (!$composer) {
            $io->error($this->trans('commands.module.update.messages.only-composer'));

            return 1;
        }

        if (!$modules) {
            $io->error(
                $this->trans('commands.module.update.messages.missing-module')
            );

            return 1;
        }

        if (count($modules) > 1) {
            $modules = " drupal/" . implode(" drupal/", $modules);
        } else {
            $modules = " drupal/" . current($modules);
        }

        if ($composer) {
            // Register composer repository
            $command = "composer config repositories.drupal composer https://packagist.drupal-composer.org";
            $this->shellProcess->exec($command, $this->root);

            $command = 'composer update ' . $modules . ' --optimize-autoloader --prefer-dist --no-dev --root-reqs ';

            if ($simulate) {
                $command .= " --dry-run";
            }

            if ($this->shellProcess->exec($command, $this->root)) {
                $io->success(
                    sprintf(
                        $this->trans('commands.module.update.messages.composer'),
                        trim($modules)
                    )
                );
            }
        }

        return 0;
    }
}
