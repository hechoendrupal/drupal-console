<?php
/**
 * @file
 * Contains Drupal\Console\Command\Alias\AliasCommand.
 */

namespace Drupal\Console\Command\Alias;

use Drupal\Console\Exception\InvalidAliasEnvironment;
use Drupal\Console\RemoteConfig;
use Drupal\Console\UserConfig;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Command\Command;
use Ssh\Session;

class AliasCommand extends Command
{

    /** @var  UserConfig */
    private $appConfig;

    /**
     * @param RemoteConfig $remote_config
     */
    public function setRemoteConfigurations(RemoteConfig $remote_config)
    {
        $this->remoteConfig = $remote_config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('alias')
            ->setDescription($this->trans('commands.alias.description'))
            ->addArgument('args', InputArgument::IS_ARRAY, $this->trans('commands.alias.arguments.args'))
            ->setHelp($this->trans('commands.alias.help'));
        ;
    }

    protected function getTarget()
    {
        $this->remoteConfig->get('');
    }

    protected function configureHost()
    {
        $this->appConfig->get('application.remote.user');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var UserConfig appConfig */
        $this->appConfig = $this->getApplication()->getConfig();
        $siteEnv = $input->getParameterOption(['--target', '-t']);
        @list($target, $env) = explode('.', $siteEnv);

        if ($this->remoteConfig->exist($target)) {
            $config = $this->remoteConfig->getTarget($target);
            if (array_key_exists($env, $config)) {
                $default = $this->appConfig->get('application.remote');

                $target_config = array_merge($default, $config[$env]);
                $command = $input->getFirstArgument();

                if (array_key_exists('host', $target_config)) {
                    $session = $this->sshConfiguration($target_config);
                    $output->writeln(
                        $this->executeRemoteCommand($session, $target_config, $command)
                    );
                }
                else {
                    $localInput = new ArrayInput([
                        'list',
                        '--drupal' => '/run/media/dmouse/Home/dmouse/public_html/www/drupal8.dev/'
                    ]);

                    print_r($localInput);

                    $this->getApplication()->doRun($localInput, $output);
                }

            }
        }
        else {
            throw new InvalidAliasEnvironment($this->trans('commands.alias.error.environment'));
        }
    }

    /**
     * @param array $config
     * @return \Ssh\Session
     */
    private function sshConfiguration($config)
    {
        $configuration = new \Ssh\Configuration(
            $config['host'],
            $config['port']
        );

        $authentication = new \Ssh\Authentication\PublicKeyFile(
            $config['user'],
            realpath($config['key']['public']),
            realpath($config['key']['private'])
        );
        return new \Ssh\Session($configuration, $authentication);
    }

    /**
     * @param $session
     * @param $command
     * @return string
     */
    private function executeRemoteCommand(Session $session, $config, $command)
    {
        try {
            return $session->getExec()->run(
                $config['console'] . " " .
                $config['options'] . " " .
                "--drupal=" . $config['drupal'] . " " .
                $command,
                null, []
            );
        } catch( \RuntimeException $e) {
            return $e->getMessage();
        }
    }
}
