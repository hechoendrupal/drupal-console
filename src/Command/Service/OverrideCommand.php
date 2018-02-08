<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Service\OverrideCommand.
 */

namespace Drupal\Console\Command\Service;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Utils\ChainQueue;

class OverrideCommand extends ContainerAwareCommand
{

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * DebugCommand constructor.
     *
     * @param $appRoot,
     * @param ChainQueue           $chainQueue,
     */
    public function __construct(
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('service:override')
            ->setDescription($this->trans('commands.service.override.description'))
            ->addArgument(
              'name',
              InputArgument::REQUIRED,
              $this->trans('commands.config.override.arguments.name')
            )
            ->addArgument(
              'key',
              InputArgument::REQUIRED,
              $this->trans('commands.config.override.arguments.key')
            )
            ->addArgument(
              'value',
              InputArgument::REQUIRED,
              $this->trans('commands.config.override.arguments.value')
            )
            ->setAliases(['so']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceName = $input->getArgument('name');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $serviceOverrideResult = $this->processServicesFile(
          $serviceName,
          $key,
          $value
        );

        $tableHeader = [
          $this->trans('commands.config.override.messages.configuration-key'),
          $this->trans('commands.config.override.messages.original'),
          $this->trans('commands.config.override.messages.updated'),
        ];
        $tableRows = $serviceOverrideResult;
        $this->getIo()->table($tableHeader, $tableRows);

        //$this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function processServicesFile($serviceName, $key, $value)
    {
      $directory = sprintf(
            '%s/%s',
            $this->appRoot,
            \Drupal::service('site.path')
        );

        $settingsServicesFile = $directory . '/services.yml';

        if (!file_exists($settingsServicesFile)) {
            // Copying default services
            $defaultServicesFile = $this->appRoot . '/sites/default/default.services.yml';
            if (!copy($defaultServicesFile, $settingsServicesFile)) {
                $this->getIo()->error(
                    sprintf(
                        '%s: %s/services.yml',
                        $this->trans('commands.service.override.messages.error-copying-file'),
                        $directory
                    )
                );

                return [];
            }
        }

        $yaml = new Yaml();

        $services = $yaml->parse(file_get_contents($settingsServicesFile));

        $services = array_merge_recursive($services, array('parameters' => array($key => $value)));
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $result[0]['service'] = $serviceName . '.' . $key;
        $result[0]['original'] = $services['parameters'][$serviceName][$key];
        $result[0]['updated'] = $value;
        $services['parameters'][$serviceName][$key] = $value;

        if (file_put_contents($settingsServicesFile, $yaml->dump($services))) {
            $this->getIo()->commentBlock(
                sprintf(
                    $this->trans('commands.service.override.messages.services-file-overwritten'),
                    $settingsServicesFile
                )
            );
        } else {
            $this->getIo()->error(
                sprintf(
                    '%s : %s/services.yml',
                    $this->trans('commands.service.override.messages.error-writing-file'),
                    $directory
                )
            );

            return [];
        }

        return $result;
    }
}
