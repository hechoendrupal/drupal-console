#!/usr/bin/php
<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Application;

/**
 * Class CodeQualityTool
 *
 * Based on
 * http://carlosbuenosvinos.com/write-your-git-hooks-in-php-and-keep-them-under-git-control/
 */
class CodeQualityTool extends Application
{
    private $output;
    private $input;

    const PHP_FILES_IN_SRC = '/^src\/(.*)(\.php)$/';
    const PHP_FILES_IN_CLASSES = '/^classes\/(.*)(\.php)$/';

    public function __construct()
    {
        parent::__construct('Code Quality Tool', '1.0.0');
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $output->writeln('<info>Code Quality Tool</info>');
        $output->writeln('<info>Fetching files:</info>');
        $files = $this->extractCommitedFiles();
        foreach ($files as $key => $file) {
            if ($key>0) {
                $output->writeln(
                  sprintf(
                    '<comment> - %s</comment>',
                    $file
                  )
                );
            }
        }

        $output->writeln('<info>Check composer</info>');
        $this->checkComposer($files);

        $output->writeln('<info>Running PHPLint</info>');
        if (!$this->phpLint($files)) {
            throw new Exception('There are some PHP syntax errors!');
        }

        $output->writeln('<info>Checking code style</info>');
        if (!$this->codeStyle($files)) {
            throw new Exception(sprintf('There are coding standards violations!'));
        }

        $output->writeln('<info>Checking code style with PHPCS (phpcbf)</info>');
        if (!$this->codeStylePsr($files, 'phpcbf')) {
            throw new Exception(sprintf('There are PHPCS (phpcbf) coding standards violations!'));
        }

        $output->writeln('<info>Checking code style with PHPCS (phpcs)</info>');
        if (!$this->codeStylePsr($files, 'phpcs')) {
            throw new Exception(sprintf('There are PHPCS (phpcs) coding standards violations!'));
        }

        // Enable on a second phase, once code base is PSR-2 compliant
//        $output->writeln('<info>Checking code mess with PHPMD</info>');
//        if (!$this->phPmd($files)) {
//            throw new Exception(sprintf('There are PHPMD violations!'));
//        }

        $output->writeln('<info>Running unit tests</info>');
        if (!$this->unitTests()) {
            throw new Exception('PHPUnit test failed!');
        }

        $output->writeln('<info>Good job dude!</info>');
    }

    private function checkComposer($files)
    {
        $composerJsonDetected = false;
        $composerLockDetected = false;

        foreach ($files as $file) {
            if ($file === 'composer.json') {
                $composerJsonDetected = true;
            }

            if ($file === 'composer.lock') {
                $composerLockDetected = true;
            }
        }

        if ($composerJsonDetected && !$composerLockDetected) {
            throw new Exception('composer.lock must be commited if composer.json is modified!');
        }
    }

    private function extractCommitedFiles()
    {
        $output = [];
        $rc = 0;

        exec('git rev-parse --verify HEAD 2> /dev/null', $output, $rc);

        $against = '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
        if ($rc == 0) {
            $against = 'HEAD';
        }

        exec("git diff-index --cached --name-status $against | egrep '^(A|M)' | awk '{print $2;}'", $output);

        return $output;
    }

    private function phpLint($files)
    {
        $needle = '/(\.php)|(\.inc)$/';
        $succeed = true;

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $processBuilder = new ProcessBuilder(['php', '-l', $file]);
            $process = $processBuilder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                $this->output->writeln($file);
                $this->output->writeln(sprintf('<error>%s</error>', trim($process->getErrorOutput())));

                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return $succeed;
    }

    private function phPmd($files)
    {
        $needle = self::PHP_FILES_IN_SRC;
        $succeed = true;
        $rootPath = realpath(__DIR__ . '/');

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $processBuilder = new ProcessBuilder(['php', 'bin/phpmd', $file, 'text', 'cleancode,codesize,unusedcode,naming']);
            $processBuilder->setWorkingDirectory($rootPath);
            $process = $processBuilder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                $this->output->writeln($file);
                $this->output->writeln(sprintf('<error>%s</error>', trim($process->getErrorOutput())));
                $this->output->writeln(sprintf('<comment>%s</comment>', trim($process->getOutput())));
                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return $succeed;
    }

    private function unitTests()
    {
        $processBuilder = new ProcessBuilder(['php', 'bin/phpunit']);
        $processBuilder->setWorkingDirectory(__DIR__ . '/');
        $processBuilder->setTimeout(3600);
        $phpunit = $processBuilder->getProcess();

        $phpunit->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $phpunit->isSuccessful();
    }

    private function codeStyle(array $files)
    {
        $succeed = true;

        foreach ($files as $file) {
            $classesFile = preg_match(self::PHP_FILES_IN_CLASSES, $file);
            $srcFile = preg_match(self::PHP_FILES_IN_SRC, $file);

            if (!$classesFile && !$srcFile) {
                continue;
            }

            $processBuilder = new ProcessBuilder(['php', 'bin/php-cs-fixer', 'fix', '--verbose', '--level=psr2', $file]);

            $processBuilder->setWorkingDirectory(__DIR__ . '/');
            $phpCsFixer = $processBuilder->getProcess();
            $phpCsFixer->run();

            if (!$phpCsFixer->isSuccessful()) {
                $this->output->writeln(sprintf('<error>%s</error>', trim($phpCsFixer->getOutput())));

                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return $succeed;
    }

    private function codeStylePsr(array $files, $command)
    {
        $succeed = true;
        $needle = self::PHP_FILES_IN_SRC;

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $processBuilder = new ProcessBuilder(['php', 'bin/'.$command, '--standard=PSR2', '-n', $file]);
            $processBuilder->setWorkingDirectory(__DIR__ . '/');
            $phpCsFixer = $processBuilder->getProcess();
            $phpCsFixer->run();

            if (!$phpCsFixer->isSuccessful()) {
                $this->output->writeln(sprintf('<error>%s</error>', trim($phpCsFixer->getOutput())));

                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return $succeed;
    }
}

$console = new CodeQualityTool();
$console->run();
