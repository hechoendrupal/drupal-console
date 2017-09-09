<?php

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class SnippetCommand
 *
 * @package Drupal\Console\Command
 */
class SnippetCommand extends Command
{

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * RestoreCommand constructor.
     *
     * @param string $appRoot
     */
    public function __construct($consoleRoot, $appRoot)
    {
        $this->consoleRoot = $consoleRoot;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('snippet')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.snippet.options.file')
            )
            ->addOption(
                'code',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.snippet.options.code')
            )
            ->addOption(
                'show-code',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.snippet.options.show-code')
            )
            ->setDescription($this->trans('commands.snippet.description'))
            ->setHelp($this->trans('commands.snippet.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = $input->getOption('file');
        $code = $input->getOption('code');
        $showCode = $input->getOption('show-code');

        if ($code) {
            eval($code);
            return 0;
        }

        if (!$file) {
            $io->error($this->trans('commands.snippet.errors.invalid-options'));

            return 1;
        }

        $file = $this->getFileAsAbsolutePath($file);
        if (!$file) {
            $io->error($this->trans('commands.snippet.errors.invalid-file'));

            return 1;
        }

        if ($showCode) {
            $code = file_get_contents($file);
            $io->writeln($code);
        }

        include_once $file;

        return 0;
    }

    private function getFileAsAbsolutePath($file)
    {
        $fs = new Filesystem();

        if ($fs->isAbsolutePath($file)) {
            return $fs->exists($file)?$file:null;
        }

        $files = [
            $this->consoleRoot.'/'.$file,
            $this->appRoot.'/'.$file
        ];

        foreach ($files as $file) {
            if ($fs->exists($file)) {
                return $file;
            }
        }

        return null;
    }
}
