<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Site\NewCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Alchemy\Zippy\Zippy;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class NewCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('site:new')
            ->setDescription($this->trans('commands.site.new.description'))
            ->addArgument(
                'site-name',
                InputArgument::REQUIRED,
                $this->trans('commands.site.new.arguments.site-name')
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                $this->trans('commands.site.new.arguments.version')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $httpClient = $this->getHttpClientHelper();

        $siteName = $input->getArgument('site-name');
        $version = $input->getArgument('version');

        if ($version) {
            $releaseSelected = $version;
        } else {
            // Getting Module page header and parse to get module Node
            $io->info(
                sprintf($this->trans('commands.site.new.messages.getting-releases'))
            );

            // Page for Drupal releases filter by Drupal 8
            $projectReleaseSelected = 'https://www.drupal.org/node/3060/release?api_version%5B%5D=7234';

            // Parse release module page to get Drupal 8 releases
            try {
                $html = $httpClient->getHtml($projectReleaseSelected);
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return;
            }
            $crawler = new Crawler($html);
            $releases = [];
            foreach ($crawler->filter('span.file a') as $element) {
                if (strpos($element->nodeValue, ".tar.gz") > 0) {
                    $releaseName = str_replace(
                        '.tar.gz', '',
                        str_replace(
                            'drupal-', '', $element->nodeValue
                        )
                    );
                    $releases[$releaseName] = $element->nodeValue;
                }
            }

            if (empty($releases)) {
                $io->error($this->trans('commands.site.new.messages.no-releases'));

                return;
            }

            $releaseSelected = $io->choice(
                $this->trans('commands.site.new.messages.release'),
                array_keys($releases)
            );
        }

        $releaseFilePath = 'http://ftp.drupal.org/files/projects/drupal-' . $releaseSelected . '.tar.gz';

        // Destination file to download the release
        $destination = tempnam(sys_get_temp_dir(), 'drupal.') . "tar.gz";

        try {
            // Start the process to download the zip file of release and copy in contrib folter
            $io->info(
                sprintf(
                    $this->trans('commands.site.new.messages.downloading'),
                    $releaseSelected
                )
            );

            $httpClient->downloadFile($releaseFilePath, $destination);

            $io->info(
                sprintf(
                    $this->trans('commands.site.new.messages.extracting'),
                    $releaseSelected
                )
            );

            $zippy = Zippy::load();
            $archive = $zippy->open($destination);
            $archive->extract('./');

            try {
                $filesyStem = new Filesystem();
                $filesyStem->rename('./drupal-' . $releaseSelected, './' . $siteName);
            } catch (IOExceptionInterface $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.site.new.messages.error-copying'),
                        $e->getPath()
                    )
                );
            }

            $io->success(
                sprintf(
                    $this->trans('commands.site.new.messages.downloaded'),
                    $releaseSelected, $siteName
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return false;
        }

        return true;
    }
}
