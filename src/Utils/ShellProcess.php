<?php
namespace Drupal\Console\Utils;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Console\Utils\Site;
use Drupal\Console\Config;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShellProcess
 * @package Drupal\Console\Utils
 */
class ShellProcess
{
    /* @var Site */
    protected $site;

    /* @var Config */
    protected $config;

    /* @var Output */
    protected $output;

    protected $shellexec_output;

    /* @var Progress */
    protected $progress;

    /**
     * @var ShellProcess
     */
    protected $process;

    /**
     * Process constructor.
     * @param Site $site
     */
    public function __construct(Site $site, Config $config)
    {
        $this->site = $site;
        $this->config = $config;
        $this->output = new ConsoleOutput();

        $this->shellexec_output
          = ($this->config->get("application.shellexec_output"))?: false;

        $this->progress = new ProgressBar($this->output);
        $this->progress->setFormat('debug_nomax');
    }

    /**
     * @param $command
     *
     * @throws ProcessFailedException
     *
     * @return Process
     */
    public function exec($command)
    {
        $rootPath = $this->site->getRoot();

        $this->process = new Process($command);
        $this->process->setWorkingDirectory($rootPath);
        $this->process->enableOutput();
        $this->process->setTimeout(null);

        if ($this->shellexec_output) {
          $this->process->run(function ($type, $buffer) {
            $this->output->writeln(
              sprintf(
                "<info>%s</info>",
                $buffer
              )
            );
          });
        }else{
          $this->progress->start();
          $this->process->start();

          while ($this->process->isRunning()) {
              $this->advance();
          }
          $this->progress->finish();
        }


        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        return $this->process->isSuccessful();
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->process->getOutput();
    }

    private function advance() {
      usleep(300000);
      $this->progress->advance();
    }
}
