<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Site\ImportCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

use Drupal\Console\Helper\DrupalHelper;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\DumpException;

class ImportLocalCommand extends Command
{
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
                NULL,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.import.local.options.environment'),
                'local'
            )
            ->setHelp($this->trans('commands.site.import.local.help'));;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $site_name = $input->getArgument('name');
        $directory = $input->getArgument('directory');

        $fileSystem = new Filesystem();
        if ( ! $fileSystem->exists($directory) ) {
          $io->error(
              sprintf(
                  $this->trans('commands.site.import.local.messages.error-missing'),
                  $directory
              )
          );

          return;
        }
        
        $drupal = $this->getDrupalHelper();
        if ( !$drupal->isValidRoot($directory) ) {
          $io->error(
              sprintf(
                  $this->trans('commands.site.import.local.messages.error-not-drupal'),
                  $directory
              )
          );

          return;
        }

        $environment = 'local';
        if ($input->hasOption('environment')) {
            $environment = $input->getOption('environment');
        }

        $site_conf = [
          $environment => [
            'root' => $drupal->getRoot(),
            'host' => 'local',
          ],
        ];
        $yaml = Yaml::dump($site_conf);

        $config = $this->getApplication()->getConfig();
        $userPath = sprintf('%s/.console/sites', $config->getUserHomeDir());
        $confFile = sprintf('%s/%s.yml', $userPath, $site_name);

        try {
          $fileSystem->dumpFile($confFile, $yaml);

        } catch (IOException $e) {
          $io->error(
              sprintf(
                  $this->trans('commands.site.import.local.messages.error-writing'),
                  $e->getMessage()
              )
          );

          return;
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
                $this->trans('commands.site.import.local.questions.directory')
            );
            $input->setArgument('directory', $directory);
        }

        $directory = $input->getArgument('name');
        if (!$directory) {
            $directory = $io->ask(
                $this->trans('commands.site.import.local.questions.name')
            );
            $input->setArgument('name', $directory);
        }

    }
}
