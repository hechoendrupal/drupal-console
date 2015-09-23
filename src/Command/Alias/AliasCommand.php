<?php

namespace Drupal\AppConsole\Command\Alias;

use Drupal\AppConsole\RemoteConfig;
use Drupal\AppConsole\UserConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\AppConsole\Command\Command;

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
            ->setDescription($this->trans('commands.remote.description'))
            ->addArgument('args', InputArgument::IS_ARRAY, $this->trans('commands.remote.arguments.args'))
            ->setHelp($this->trans('commands.remote.help'));
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
        list($target, $env) = explode('.', $siteEnv);

        if ($this->remoteConfig->exist($target)) {
            $config = $this->remoteConfig->getTarget($target);
            if (array_key_exists($env, $config)) {
                $default = $this->appConfig->get('application.remote');

                $target_config = array_merge($default, $config[$env]);
                $command = $input->getFirstArgument();
                $configuration = new \Ssh\Configuration(
                    $target_config['host'],
                    $target_config['port']
                );

                $authentication = new \Ssh\Authentication\PublicKeyFile(
                    $target_config['user'],
                    realpath($target_config['key']['public']),
                    realpath($target_config['key']['private'])
                );

                try {
                    $session = new \Ssh\Session($configuration, $authentication);
                    $output->writeln(
                        $session->getExec()->run(
                            $target_config['console'] . " " .
                            $target_config['options'] . " " .
                            "--drupal=" . $target_config['drupal'] . " " .
                            $command,
                            null, []
                        )
                    );
                } catch( \RuntimeException $e) {
                    $output->writeln($e->getMessage());
                }
            }
        }
    }
}