<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\InitCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class InitCommand extends ContainerAwareCommand
{

    private $files = [
      [
        'source' => 'config/dist/config.yml',
        'destination' => 'config.yml'
      ],
      [
        'source' => 'config/dist/chain.yml',
        'destination' => 'chain/sample.yml'
      ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('init')
          ->setDescription($this->trans('commands.init.description'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        $message = $this->getHelperSet()->get('message');
        $basePath = __DIR__ . '/../../';
        $userPath = $config->getUserHomeDir() . '/.console/';
        $copiedFiles = [];

        foreach ($this->files as $file) {
            $source = $basePath . $file['source'];
            $destination = $userPath . '/' . $file['destination'];
            if ($this->copyFile($source, $destination)) {
                $copiedFiles[] = $file['destination'];
            }
        }

        if ($copiedFiles) {
            $message->showCopiedFiles($output, $copiedFiles);
        }
    }

    public function copyFile($source, $destination)
    {
        if (file_exists($destination)) {
            return false;
        }

        $filePath = dirname($destination);
        if (!is_dir($filePath)) {
            mkdir($filePath);
        }

        return copy(
          $source,
          $destination
        );
    }
}
