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

    /* @var $connectionInfoKeys array */
    protected $connectionInfoKeys = [
      'driver',
      'host',
      'database',
      'port',
      'username',
      'password'
    ];

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
        $status_data = $this->getStatusData($input);
        $output->writeln($status_data);
    }

    private function getStatusData(InputInterface $input)
    {
        $status_data =  array(
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
            'root'        => '',
            'site_path'   => '',
            'theme_path'  => '',
            'config_path' => '',
            'tmp_path'    => ''
          )
      );
      // ==================
      // Collect Data
      // ==================
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
            'password'          => $db_info['default']['password'],
            'connection_string' => $db_info['default']['driver'].'//'.$db_info['default']['username'].':'.$db_info['default']['password'].'@'.$db_info['default']['host'].((!empty($db_info['default']['port'])) ? ':'.$db_info['default']['port'] : '').'/'.$db_info['default']['database']
          );
      // ==================
      // Filters
      // ==================
      if (!($input->getOption('all'))) {
          $status_data = array(
            'console_version'  => $status_data['console_version'],
            'drupal_version'   => $status_data['drupal_version'],
            'database'         => array('connection_string' => $status_data['database']['connection_string']),
            'theme'            => array('defaul' => $status_data['theme']['default']),
            'directories'      => array('root' => $status_data['directories']['root'])
        );
      }
        if ($input->getArgument('field-group') != null) {
            switch ($input->getArgument('field-group')) {
          case 'version':
            $status_data = array(
              'console_version' => $status_data['console_version'],
              'drupal_version'  => $status_data['drupal_version'],
              );
            break;
          case 'database':
            $status_data  = $status_data['database'];
            break;
          case 'theme':
            $status_data = $status_data['theme'];
            break;
          case 'directories':
            $status_data = $status_data['directories'];
            break;
          default:
            # code...
            break;
        }
        }
      // ==================
      // Output Format
      // ==================
      switch ($input->getOption('format')) {
        case self::FORMAT_JSON:
          $status_data =  json_encode($status_data);
          break;
        case self::FORMAT_CSV:
          # code...
          break;
        default:
          $formatter = $this->getHelper('formatter');
          $message = '<info>Site Status</info>' . PHP_EOL;
          foreach ($status_data as $field_group => $field_value) {
              if (is_array($field_value)) {
                  $seccion = '';
                  $section_msg = '';
                  $section = $field_group;
                  foreach ($field_value as $field => $value) {
                      $line = PHP_EOL. $field . ': ' . $value;
                      $section_msg = $section_msg . $line;
                  }
                  $formattedLine = $formatter->formatSection($section, $section_msg);
                  $message = $message . PHP_EOL . PHP_EOL .$formattedLine ;
              } else {
                  $line = PHP_EOL . $field_group . ': ' . $field_value;
                  $message = $message . $line;
              }
          }
          $status_data = $message;
          break;
      }
        return $status_data;
    }
}
