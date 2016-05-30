<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\SearchCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class SearchCommand extends Command
{
    use ContainerAwareCommandTrait;

    protected function configure()
    {
        $this
            ->setName('module:search')
            ->setDescription($this->trans('commands.module.search.description'))
            ->addArgument(
                'module',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                $this->trans('commands.module.search.module')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.search.options.composer')
            );
    }


    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $composer = $input->getOption('composer');
        $modules = $input->getArgument('module');

        if (!$modules) {
            throw new \Exception($this->trans('commands.module.search.messages.module-to-search'));

            return 1;
        }

        if (!$composer) {
            throw new \Exception($this->trans('commands.module.search.messages.only-composer'));

            return 1;
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);


        $type = $input->getOption('composer');
        $modules = $input->getArgument('module');

        if ($modules) {
            $config = $this->getApplication()->getConfig();
            foreach ($modules as $module) {
                $url = sprintf(
                    '%s/search.json?q=',
                    $config->get('application.composer.packages.default')) .
                    $module;

                try {
                    $json = $this->getApplication()->getHttpClientHelper()->getUrlAsJson($url);

                } catch (\Exception $e) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.module.search.messages.no-results'),
                            $module
                        )
                    );

                    return 1;
                }

                if (0 == $json->total) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.module.search.messages.no-results'),
                            $module
                        )
                    );

                    return 1;
                }

                foreach ($json->results as $data) {
                  $tableHeader = [
                    '<info>'.$data->name.'</info>'
                  ];

                  $tableRows = [];

                  $tableRows[] = [ $data->description ];

                  $tableRows[] = [
                      '<comment>'.
                      sprintf(
                        $this->trans('commands.module.search.messages.downloads'),
                        $data->downloads)
                      .'</comment>'
                  ];

                  $io->table($tableHeader, $tableRows, 'compact');
                }
            }
        }

    }
}
