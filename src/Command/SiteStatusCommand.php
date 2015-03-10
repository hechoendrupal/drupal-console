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
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $systemManager = $this->getSystemManager();
        $requirements = $systemManager->listRequirements();

        $table = $this->getHelperSet()->get('table');

        $table->setlayout($table::LAYOUT_COMPACT);

        $table->addRow([
          '<comment>Application</comment>',
          null
        ]);
        $table->addRow([
          'Drupal Console',
          $this->getApplication()->getVersion()
        ]);
        $table->addRow([null,null]);

        $table->addRow([
          '<comment>System Info</comment>',
          null
        ]);

        foreach ($requirements as $requirement) {
            $table->addRow([
              $requirement['title'],
              $requirement['value']
            ]);
        }
        $table->addRow([null,null]);

        $table->addRow([
          '<comment>Database connection</comment>',
          null
        ]);

        $connectionInfo = \Drupal\Core\Database\Database::getConnectionInfo();

        foreach ($this->connectionInfoKeys as $connectionInfoKey) {
            $table->addRow([
              $connectionInfoKey,
              $connectionInfo['default'][$connectionInfoKey]
            ]);
        }

        $table->addRow([
          'connection',
          $connectionInfo['default']['driver'].'//'.$connectionInfo['default']['username'].':'.$connectionInfo['default']['password'].'@'.$connectionInfo['default']['host'].((!empty($connectionInfo['default']['port'])) ? ':'.$connectionInfo['default']['port'] : '').'/'.$connectionInfo['default']['database']
        ]);

        $table->render($output);
    }
}
