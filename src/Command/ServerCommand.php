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
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ServerCommand
 * @package Drupal\Console\Command
 */
class ServerCommand extends BaseCommand
{
    use CommandTrait;

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

        $address = $input->getArgument('address');
        if (false === strpos($address, ':')) {
            $address = sprintf(
                '%s:8088',
                $address
            );
        }

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
        $process = $processBuilder->getProcess();
        $process->setWorkingDirectory($this->get('site')->getRoot());
        $process->setTty('true');
        $process->run();

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
            $this->getApplication()->getConfig()->getUserHomeDir()
        );

        if (file_exists($router)) {
            return $router;
        }

        $router = sprintf(
            '%s/config/dist/router.php',
            $this->getApplication()->getDirectoryRoot()
        );

        if (file_exists($router)) {
            return $router;
        }

        return null;
    }
}
