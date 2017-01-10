<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ServerCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ServerCommand
 *
 * @package Drupal\Console\Command
 */
class ServerCommand extends Command
{
    use CommandTrait;

    protected $appRoot;

    protected $configurationManager;

    /**
     * ServerCommand constructor.
     *
     * @param $appRoot
     * @param $configurationManager
     */
    public function __construct($appRoot, $configurationManager)
    {
        $this->appRoot = $appRoot;
        $this->configurationManager = $configurationManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('server')
            ->setDescription($this->trans('commands.server.description'))
            ->addArgument(
                'address',
                InputArgument::OPTIONAL,
                $this->trans('commands.server.arguments.address'),
                '127.0.0.1:8088'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;
        $address = $this->validatePort($input->getArgument('address'));

        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find()) {
            $io->error($this->trans('commands.server.errors.binary'));
            return;
        }

        $router = $this->getRouterPath();
        $cli = sprintf(
            '%s %s %s %s',
            $binary,
            '-S',
            $address,
            $router
        );

        if ($learning) {
            $io->commentBlock($cli);
        }

        $io->success(
            sprintf(
                $this->trans('commands.server.messages.executing'),
                $binary
            )
        );

        $processBuilder = new ProcessBuilder(explode(' ', $cli));
        $processBuilder->setTimeout(NULL);
        $processBuilder->setWorkingDirectory($this->appRoot);

        $process = $processBuilder->getProcess();
        try {
          $process->setTty(TRUE);
          $outputCallback = NULL;
        }
        catch(RuntimeException $e) {
          // We can't use TTY.  Handle output manually.
          $outputCallback = function($type, $message) use ($io) {
            $io->write($message);
          };
        }

        $process->run($outputCallback);

        if (!$process->isSuccessful()) {
            $io->error($process->getErrorOutput());
        }
    }

    /**
     * @return null|string
     */
    private function getRouterPath()
    {
        $router = sprintf(
            '%s/.console/router.php',
            $this->configurationManager->getHomeDirectory()
        );

        if (file_exists($router)) {
            return $router;
        }

        $router = sprintf(
            '%s/config/dist/router.php',
            $this->configurationManager->getApplicationDirectory()
        );

        if (file_exists($router)) {
            return $router;
        }

        return null;
    }

    /**
     * @param string $address
     * @return string
     */
    private function validatePort($address)
    {
        if (false === strpos($address, ':')) {
            $host = $address;
            $port = '8088';
        } else {
            $host = explode(':', $address)[0];
            $port = explode(':', $address)[1];
        }

        if (fsockopen($host, $port)) {
            $port = rand(8888, 9999);
            $address = sprintf(
                '%s:%s',
                $host,
                $port
            );

            $address = $this->validatePort($address);
        }

        return $address;
    }
}
