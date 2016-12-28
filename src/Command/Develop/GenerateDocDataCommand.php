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
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;

class GenerateDocDataCommand extends Command
{
    use CommandTrait;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:doc:data')
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

        $data = $this->getApplication()->getData();
        if ($file) {
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

            return 0;
        }

        $io->write(json_encode($data, JSON_PRETTY_PRINT));
    }
}
