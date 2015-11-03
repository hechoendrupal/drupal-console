<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ModuleDownloadCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Zippy\Zippy;

class ModuleDownloadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription($this->trans('commands.module.install.description'))
            ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.module.install.options.module'))
            ->addArgument('version', InputArgument::OPTIONAL, $this->trans('commands.module.download.options.version'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $httpClient = $this->getHttpClientHelper();

        $module = $input->getArgument('module');

        $version = $input->getArgument('version');

        if ($version) {
            $release_selected = $version;
        } else {
            // Getting Module page header and parse to get module Node
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.module.download.messages.getting-releases'),
                    implode(',', array($module))
                ).'</info>'
            );

            try {
                $link = $httpClient->getHeader('https://www.drupal.org/project/'.$module, 'link');
            } catch (\Exception $e) {
                $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
                return;
            }

            $header_link = explode(';', $link);
            $project_node = str_replace('<', '', str_replace('>', '', $header_link[0]));
            $project_release_d8 = $project_node.'/release?api_version%5B%5D=7234';

            // Parse release module page to get Drupal 8 releases
            try {
                $html = $httpClient->getHtml($project_release_d8);
            } catch (\Exception $e) {
                print_r($e->getMessage());
                $output->writeln('[+] <error>'.$e->getMessage().'</error>');

                return;
            }

            $crawler = new Crawler($html);
            $releases = [];
            foreach ($crawler->filter('span.file a') as $element) {
                if (strpos($element->nodeValue, '.tar.gz') > 0) {
                    $release_name = str_replace(
                        '.tar.gz',
                        '',
                        str_replace(
                            $module.'-',
                            '',
                            $element->nodeValue
                        )
                    );
                    $releases[$release_name] = $element->nodeValue;
                }
            }

            if (empty($releases)) {
                $output->writeln(
                    '[+] <error>'.sprintf(
                        $this->trans('commands.module.download.messages.no-releases'),
                        implode(',', array($module))
                    ).'</error>'
                );

                return;
            }

            // List module releases to enable user to select his favorite release
            $questionHelper = $this->getQuestionHelper();

            $question = new ChoiceQuestion(
                $this->trans('commands.module.download.messages.select-release'),
                array_combine(array_keys($releases), array_keys($releases)),
                '0'
            );

            $release_selected = $questionHelper->ask($input, $output, $question);
        }

        // Start the process to download the zip file of release and copy in contrib folter
        $output->writeln(
            '[-] <info>'.
            sprintf(
                $this->trans('commands.module.download.messages.downloading'),
                $module,
                $release_selected
            ).
            '</info>'
        );

        $release_file_path = 'http://ftp.drupal.org/files/projects/'.$module.'-'.$release_selected.'.tar.gz';

        // Destination file to download the release
        $destination = tempnam(sys_get_temp_dir(), 'console.').'.tar.gz';

        try {
            $httpClient->downloadFile($release_file_path, $destination);

            // Determine destination folder for contrib modules
            $drupal = $this->getDrupalHelper();
            $drupalRoot = $drupal->getRoot();
            if ($drupalRoot) {
                $module_contrib_path = $drupalRoot . '/modules/contrib';
            } else {
                $output->writeln(
                    '[-] <info>'.
                    sprintf(
                        $this->trans('commands.module.download.messages.outside-drupal'),
                        $module,
                        $release_selected
                    ).
                    '</info>'
                );
                $module_contrib_path = getcwd() . '/modules/contrib';
            }

            // Create directory if does not exist
            if (!file_exists($module_contrib_path)) {
                if (!mkdir($module_contrib_path, 0777, true)) {
                    $output->writeln(
                        ' <error>'. $this->trans('commands.module.download.messages.error-creating-folder') . ': ' . $module_contrib_path .'</error>'
                    );
                    return;
                }
            }

            // Prepare release to unzip and untar
            $zippy = Zippy::load();
            $archive = $zippy->open($destination);
            $archive->extract($module_contrib_path . '/');

            unlink($destination);

            $output->writeln(
                '[-] <info>'.sprintf(
                    $this->trans('commands.module.download.messages.downloaded'),
                    $module,
                    $release_selected,
                    $module_contrib_path
                ).'</info>'
            );
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$e->getMessage().'</error>');

            return;
        }

        return true;
    }
}
