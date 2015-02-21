<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\SiteStatusCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  This command provides a view of the current drupal installation.
 *
 *  @category site
 */
class SiteStatusCommand extends ContainerAwareCommand
{
    const FORMAT_STANDARD_OUTPUT = 'stdo';
    const FORMAT_JSON = 'json';
    const FORMAT_CSV = 'csv';
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('site:status')
          ->setDescription($this->trans('commands.site.status.description'))
          ->addArgument(
              'field-group',
              InputArgument::OPTIONAL,
              $this->trans('commands.site.status.arguments.fields-group')
          )
          ->addOption(
              'all',
              'a',
              null,
              $this->trans('commands.site.status.options.all')
          )
          ->addOption(
              'format',
              null,
              InputOption::VALUE_REQUIRED,
              $this->trans('commands.site.status.options.format')
          );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $this->getHelperSet()->get('table');
        // Options
        if ($input->getOption('all')){
          $show_all = true;
        } else {
          $show_all = false;
        }
        $output_format = $input->getOption('format');
        // Arguments
        $fields_group = $input->getArgument('field-group');

        $status = $this->getStatusData();

        $table->setHeaders(
          [
            $this->trans('commands.site.mode.messages.configuration-key'),
            $this->trans('commands.site.mode.messages.original'),
            $this->trans('commands.site.mode.messages.updated')
          ]);
        $table->setlayout($table::LAYOUT_COMPACT);
        $table->setRows($configurationOverrideResult);
        $table->render($output);
        $output->writeln($status['drupal_version']);

    }

    private function getStatusData($filter,$format = self::FORMAT_STANDARD_OUTPUT )
    {
        $status_data =  array (
          'console_version' => '',
          'drupal_version'  => '',
          'database'        => array(
              'driver'            => '',
              'hostname'          => '',
              'name'              => '',
              'port'              => '',
              'username'          => '',
              'password'          => '',
              'connection_string' => '',
              'status'            => ''
            ),
          'theme'           => array(
              'defaul'  => '',
              'admin'   => ''
            ),
          'directories'     => array(
              'root_directory' => '',
              'site_path'      => '',
              'theme_path'     => '',
              'config_path'    => '',
              'tmp_path'       => ''
            )
        );

        $status_data['console_version'] = $this->getApplication()->getVersion();
        $status_data['drupal_version'] = \Drupal::VERSION;
        // database
        $db_info = \Drupal\Core\Database\Database::getConnectionInfo();
        $status_data['database'] = $arrayName = array(
            'driver'            => $db_info['default']['driver'],
            'hostname'          => $db_info['default']['host'],
            'name'              => $db_info['default']['database'],
            'port'              => $db_info['default']['port'],
            'username'          => $db_info['default']['username'],
            'password'          => $db_info['default']['password']
          );
        // $status['drupal_version'] =
print_r($status_data);die();
        return $status_data;

    }


}
