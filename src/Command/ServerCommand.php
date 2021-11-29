<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ServerCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\PhpExecutableFinder;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;

/**
 * Class ServerCommand
 *
 * @package Drupal\Console\Command
 */
class ServerCommand extends Command
{
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ConfigurationManager
     */
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
            )->setAliases(['serve']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $address = $this->validatePort($input->getArgument('address'));

        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find()) {
            $this->getIo()->error($this->trans('commands.server.errors.binary'));
            return 1;
        }

        $router = $this->configurationManager
            ->getVendorCoreDirectory() . 'router.php';

        $processBuilder = new ProcessBuilder([$binary, '-S', $address, $router]);
        $processBuilder->setTimeout(null);
        $processBuilder->setWorkingDirectory($this->appRoot);
        $process = $processBuilder->getProcess();

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.server.messages.executing'),
                $binary
            )
        );

        $this->getIo()->commentBlock(
            sprintf(
                $this->trans('commands.server.messages.listening'),
                'http://'.$address
            )
        );

        if ($this->getIo()->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $callback = [$this, 'outputCallback'];
        } else {
            $callback = null;
        }

        // Use the process helper to copy process output to console output.
        $this->getHelper('process')->run($output, $process, null, $callback);

        if (!$process->isSuccessful()) {
            $this->getIo()->error($process->getErrorOutput());
            return 1;
        }

        return 0;
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

    public function outputCallback($type, $buffer)
    {
        // TODO: seems like $type is Process::ERR always
        echo $buffer;
    }
}
