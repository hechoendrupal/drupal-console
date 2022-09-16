<?php

namespace Drupal\Console\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\GenerateCommand;
use Drupal\Console\Generator\DockerInitGenerator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DockerInitCommand
 *
 * @package Drupal\Console\Command
 */
class DockerInitCommand extends GenerateCommand
{
    /**
     * @var DockerInitGenerator
     */
    protected $generator;

    /**
     * InitCommand constructor.
     *
     * @param DockerInitGenerator $generator
     */
    public function __construct(
        DockerInitGenerator $generator
    ) {
        $this->generator = $generator;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('docker:init')
            ->setDescription(
                $this->trans('commands.docker.init.description')
            )
            ->setHelp($this->trans('commands.docker.init.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $fs = new Filesystem();
        $dockerComposeFiles = [
            $this->drupalFinder->getComposerRoot() . '/docker-compose.yml',
            $this->drupalFinder->getComposerRoot() . '/docker-compose.yaml'
        ];

        $dockerComposeFile = $this->validateFileExists(
            $fs,
            $dockerComposeFiles,
            false
        );

        if (!$dockerComposeFile) {
            $dockerComposeFile = $this->drupalFinder->getComposerRoot() . '/docker-compose.yml';
        }

        $this->backUpFile($fs, $dockerComposeFile);

        $parameters = [
            'docker_compose_file' => $dockerComposeFile
        ];

        $this->generator->setIo($io);
        $this->generator->generate($parameters);
    }

}
