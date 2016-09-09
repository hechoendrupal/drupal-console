<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\GenerateDocDataCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;

class GenerateDocDataCommand extends Command
{
    use CommandTrait;

    /**
     * GenerateDocDataCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('develop:generate:doc:data')
            ->setDescription(
                $this->trans('commands.generate.doc.data.description')
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.data.options.file')
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = null;
        if ($input->hasOption('file')) {
            $file = $input->getOption('file');
        }

        if (!$file) {
            $io->error(
                $this->trans('commands.generate.doc.data.messages.missing_file')
            );

            return 1;
        }

        $data = $this->getApplication()->getData();
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
