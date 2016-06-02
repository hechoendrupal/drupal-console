<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportSingleCommand.
 */
namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class ImportSingleCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:import:single')
            ->setDescription($this->trans('commands.config.import.single.description'))
            ->addArgument(
                'name', InputArgument::REQUIRED,
                $this->trans('commands.config.import.single.arguments.name')
            )
            ->addArgument(
                'file', InputArgument::REQUIRED,
                $this->trans('commands.config.import.single.arguments.file')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('name');
        $fileName = $input->getArgument('file');
        $config = $this->getDrupalService('config.factory')->getEditable($configName);
        $ymlFile = new Parser();

        if (!empty($fileName) && file_exists($fileName)) {
            $value = $ymlFile->parse(file_get_contents($fileName));
        } else {
            $value = $ymlFile->parse(stream_get_contents(fopen("php://stdin", "r")));
        }

        if (empty($value)) {
            $io->error($this->trans('commands.config.import.single.messages.empty-value'));

            return;
        }
        $config->setData($value);

        try {
            $config->save();
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.config.import.single.messages.success'),
                $configName
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $name = $input->getArgument('name');
        if (!$name) {
            $configFactory = $this->getDrupalService('config.factory');
            $names = $configFactory->listAll();
            $name = $io->choiceNoList(
                $this->trans('commands.config.import.single.questions.name'),
                $names
            );
            $input->setArgument('name', $name);
        }
        $file = $input->getArgument('file');
        if (!$file) {
            $file = $io->ask(
                $this->trans('commands.config.import.single.questions.file')
            );
            $input->setArgument('file', $file);
        }
    }
}
