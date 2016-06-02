<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\ImportLocalCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ImportLocalCommand
 * @package Drupal\Console\Command\Site
 */
class ImportLocalCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('site:import:local')
            ->setDescription($this->trans('commands.site.import.local.description'))
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                $this->trans('commands.site.import.local.arguments.name')
            )
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                $this->trans('commands.site.import.local.arguments.directory')
            )
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.import.local.options.environment')
            )
            ->setHelp($this->trans('commands.site.import.local.help'));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $siteName = $input->getArgument('name');
        $directory = $input->getArgument('directory');

        $fileSystem = $this->get('filesystem');
        if (!$fileSystem->exists($directory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.import.local.messages.error-missing'),
                    $directory
                )
            );

            return 1;
        }
        
        $drupal = $this->get('site');
        if (!$drupal->isValidRoot($directory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.import.local.messages.error-not-drupal'),
                    $directory
                )
            );

            return 1;
        }

        $environment = $input->getOption('environment')?:'local';

        $siteConfig = [
          $environment => [
            'root' => $drupal->getRoot(),
            'host' => 'local',
          ],
        ];

        $yaml = $this->get('yaml');
        $dump = $yaml::dump($siteConfig);

        $config = $this->getApplication()->getConfig();
        $userPath = sprintf('%s/.console/sites', $config->getUserHomeDir());
        $configFile = sprintf('%s/%s.yml', $userPath, $siteName);

        try {
            $fileSystem->dumpFile($configFile, $dump);
        } catch (\Exception $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.import.local.messages.error-writing'),
                    $e->getMessage()
                )
            );

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.site.import.local.messages.imported')
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getArgument('directory');
        if (!$directory) {
            $directory = $io->ask(
                $this->trans('commands.site.import.local.questions.directory'),
                getcwd()
            );
            $input->setArgument('directory', $directory);
        }

        $name = $input->getArgument('name');
        if (!$name) {
            $name = $io->ask(
                $this->trans('commands.site.import.local.questions.name')
            );
            $input->setArgument('name', $name);
        }
    }
}
