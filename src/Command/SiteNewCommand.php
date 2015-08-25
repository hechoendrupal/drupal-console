<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ModuleDownloadCommand.
 */

namespace Drupal\AppConsole\Command;

use Alchemy\Zippy\Zippy;
use Buzz\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class SiteNewCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('site:new')
            ->setDescription($this->trans('commands.site.new.description'))
            ->addArgument('site-name', InputArgument::REQUIRED, $this->trans('commands.site.new.arguments.site-name'))
            ->addArgument('version', InputArgument::OPTIONAL, $this->trans('commands.site.new.arguments.version'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client =  new Browser();
        $site_name = $input->getArgument('site-name');
        $version = $input->getArgument('version');

        if ($version) {
            $release_selected = '8.x-' . $version;
        } else {
            // Getting Module page header and parse to get module Node
            $output->writeln('[+] <info>' . sprintf($this->trans('commands.site.new.messages.getting-releases')) . '</info>');

            // Page for Drupal releases filter by Drupal 8
            $project_release_d8 = 'https://www.drupal.org/node/3060/release?api_version%5B%5D=7234';

            // Parse release module page to get Drupal 8 releases
            try {
                $response = $client->get($project_release_d8);
                $html = $response->getContent();
            } catch (\Exception $e) {
                $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
                return;
            }
            $crawler = new Crawler($html);
            $releases = [];
            foreach ($crawler->filter('span.file a') as $element) {
                if (strpos($element->nodeValue, ".tar.gz") > 0) {
                    $release_name = str_replace(
                        '.tar.gz', '',
                        str_replace(
                            'drupal-', '', $element->nodeValue
                        )
                    );
                    $releases[$release_name] = $element->nodeValue;
                }
            }

            if (empty($releases)) {
                $output->writeln('[+] <error>' . $this->trans('commands.module.site.new.no-releases') . '</error>');
                return;
            }

            // List module releases to enable user to select his favorite release
            $questionHelper = $this->getQuestionHelper();

            $question = new ChoiceQuestion(
                'Please select your favorite release',
                array_combine(array_keys($releases), array_keys($releases)),
                0
            );

            $release_selected = $questionHelper->ask($input, $output, $question);
        }

        $release_file_path = 'http://ftp.drupal.org/files/projects/drupal-' . $release_selected . '.tar.gz';

        // Destination file to download the release
        $destination = tempnam(sys_get_temp_dir(), 'drupal.') . "tar.gz";

        try {
            // Start the process to download the zip file of release and copy in contrib folter
            $output->writeln(
                '[+] <info>' .
                sprintf(
                    $this->trans('commands.site.new.messages.downloading'),
                    $release_selected
                ) .
                '</info>'
            );

            // Save release file
            file_put_contents($destination, file_get_contents($release_file_path));

            $output->writeln(
                '[+] <info>' .
                sprintf(
                    $this->trans('commands.site.new.messages.extracting'),
                    $release_selected
                ) .
                '</info>'
            );

            $zippy = Zippy::load();
            $archive = $zippy->open($destination);
            $archive->extract('./');

            try {
                $fs = new Filesystem();
                $fs->rename('./drupal-' . $release_selected, './' . $site_name);
            } catch (IOExceptionInterface $e) {
                $output->writeln(
                    '[+] <error>'. sprintf(
                        $this->trans('commands.site.new.messages.error-copying'),
                        $e->getPath()
                    )  . '</error>'
                );
            }

            $output->writeln(
                '[+] <info>' . sprintf(
                    $this->trans('commands.site.new.messages.downloaded'),
                    $release_selected, $site_name
                ) . '</info>'
            );
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;
        }
        return true;
    }
}
