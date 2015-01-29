<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ModuleDownloadCommand.
 */

namespace Drupal\AppConsole\Command;

use Alchemy\Zippy\Zippy;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\OutputInterface;


class ModuleDownloadCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('module:download')
      ->setDescription($this->trans('commands.module.install.description'))
      ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.module.install.options.module'));
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $client = $this->getHttpClient();

    $module = $input->getArgument('module');

    // Getting Module page header and parse to get module Node
    $output->writeln('[+] <info>' . sprintf($this->trans('commands.module.download.messages.getting-releases'), implode(',', array($module))) . '</info>');

    $response = $client->head('https://www.drupal.org/project/' . $module);
    $header_link = explode(";", $response->getHeader('link'));

    $project_node = str_replace('<', '', str_replace('>', '', $header_link[0]));
    $project_release_d8 = $project_node . '/release?api_version%5B%5D=7234';

    // Parse release module page to get Drupal 8 releases
    try {
      $response = $client->get($project_release_d8);
      $html = $response->getBody()->__tostring();
    }
    catch (\Exception $e) {
      $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
      return;
    }

    $crawler = new Crawler($html);
    $releases = [];
    foreach ($crawler->filter('span.file a') as $element) {
      if (strpos($element->nodeValue, ".tar.gz")>0) {
        $release_name = str_replace(
          '.tar.gz', '' ,
          str_replace(
            $module .'-', '', $element->nodeValue
          )
        );
        $releases[$release_name] = $element->nodeValue;
      }
    }

    if(empty($releases)) {
      $output->writeln('[+] <error>' . sprintf($this->trans('commands.module.download.messages.no-releases'), implode(',', array($module))) . '</error>');
      return;
    }

    // List module releases to enable user to select his favorite release
    $questionHelper  = $this->getQuestionHelper();

    $question = new ChoiceQuestion(
        'Please select your favorite release',
        array_keys($releases),
        0
    );

    $release_selected = $questionHelper->ask($input, $output, $question);

    // Start the process to download the zip file of release and copy in contrib folter
    $output->writeln('[+] <info>' . sprintf($this->trans('commands.module.download.messages.downloading'), $module, $release_selected) . '</info>');

    $release_file_path = 'http://ftp.drupal.org/files/projects/' . $module .'-' . $release_selected . '.tar.gz';

    // Destination file to download the release
    $destination = tempnam(sys_get_temp_dir(), 'console.') . "tar.gz";

    try {

      $client->get($release_file_path, ['save_to' => $destination]);

      // Determine destination folder for contrib modules
      $drupalBoostrap = $this->getHelperSet()->get('bootstrap');
      $module_contrib_path = $drupalBoostrap->getDrupalRoot() . "/modules/contrib";

      // Create directory if does not exist
      if (file_exists(dirname($module_contrib_path))) {
        mkdir($module_contrib_path, 0777, true);
      }

      // Preper release to unzip and untar
      $zippy = Zippy::load();
      $archive = $zippy->open($destination);
      $archive->extract($module_contrib_path . '/');

      fclose($destination . ".tar.gz");

      $output->writeln('[+] <info>' . sprintf($this->trans('commands.module.download.messages.installed'), $module, $release_selected) . '</info>');
    }
    catch (\Exception $e) {
      $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
      return;
    }
    return TRUE;
  }
}
