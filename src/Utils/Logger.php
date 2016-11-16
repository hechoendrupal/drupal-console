<?php

namespace Drupal\Console\Utils;

use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class Logger {

    protected $handle;
    /**
     * @var ConsoleOutputInterface
     */
    protected $output;

    /**
     * Logger constructor.
     * @param $root
     */
    public function __construct($root) {
        $this->handle = null;
        $this->init($root);
    }

    protected function init($root) {
        $loggerFile = $root.'/console/log/' . date('Y-m-d') . '.log';
        if (!is_file($loggerFile)) {
            try {
                $directoryName = dirname($loggerFile);
                if (!is_dir($directoryName )) {
                    mkdir($directoryName, 0777, TRUE);
                }
                touch($loggerFile);
            } catch (\Exception $e) {
                $this->output = new ConsoleOutput();
                return;
            }
        }

        if (!is_writable($loggerFile)) {
            $this->output = new ConsoleOutput();
            return;
        }

        try {
            $this->handle = fopen($loggerFile, 'a+');
            $this->output = new StreamOutput($this->handle);
            return;
        } catch (\Exception $e) {
            $this->output = new ConsoleOutput();
            return;
        }
    }

    public function writeln($message) {
        if ($this->handle) {
            $message = sprintf(
                '%s %s',
                date('h:i:s'),
                $message
            );
        }
        $this->output->writeln($message);
    }

//    public function closeHandler() {
//        if ($this->handle) {
//            fclose($this->handle);
//        }
//    }
}