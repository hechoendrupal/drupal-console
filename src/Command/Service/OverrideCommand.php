<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Service\OverrideCommand.
 */
namespace Drupal\Console\Command\Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
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
     * @var Directory
     */
    private $directory;

    /**
     * @var ServiceFile
     */
    private $serviceFile = '/services.yml';

    /**
     * OverrideCommand constructor.
     *
     * @param $appRoot,
     * @param ChainQueue $chainQueue,
     */
    public function __construct(
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;

        $this->directory = sprintf(
            '%s/%s',
            $appRoot,
            \Drupal::service('site.path')
        );

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
                $this->trans('commands.service.override.arguments.name')
            )
            ->addOption(
                'key',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.service.override.options.key')
            )
            ->addOption(
                'value',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.service.override.options.value')
            )
            ->setAliases(['so']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $services = $this->getServiceFileAsParameters();

        if ($name) {
            if (!in_array($name, array_keys($services['parameters']))) {
                $this->getIo()->warning(
                    sprintf(
                        $this->trans('commands.service.override.messages.invalid-name'),
                        $name
                    )
                );
                $name = null;
            }
        } else {
            $name = $this->getIo()->choiceNoList(
                $this->trans('commands.service.override.questions.name'),
                array_keys($services['parameters'])
            );
            $input->setArgument('name', $name);
        }

        $key = $input->getOption('key');
        if (!$key) {
            if (!$services['parameters'][$name]) {
                $this->getIo()->newLine();
                $this->getIo()->errorLite($this->trans('commands.config.override.messages.invalid-config-file'));
                $this->getIo()->newLine();
                return 0;
            }

            $service = $services['parameters'][$name];
            $input->setOption('key', $this->getKeysFromServices($service));
        }

        $value = $input->getOption('value');
        if (!$value) {
            foreach ($input->getOption('key') as $name) {
                $value[] = $this->getIo()->ask(
                    sprintf(
                        $this->trans('commands.config.override.questions.value'),
                        $name
                    )
                );
            }
            $input->setOption('value', $value);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceName = $input->getArgument('name');
        $keys = $input->getOption('key');
        $values = $input->getOption('value');


        $serviceOverrideResult = [];
        foreach ($keys as $index => $key) {
            $result = $this->processServicesFile(
                $serviceName,
                $key,
                $values[$index]
            );
            $serviceOverrideResult = array_merge($serviceOverrideResult, $result);
        }
        $this->getIo()->info($this->trans('commands.service.override.messages.service-name'), false);
        $this->getIo()->comment($serviceName);

        $tableHeader = [
            $this->trans('commands.service.override.messages.service-key'),
            $this->trans('commands.service.override.messages.original'),
            $this->trans('commands.service.override.messages.updated'),
        ];
        $tableRows = $serviceOverrideResult;
        $this->getIo()->table($tableHeader, $tableRows);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function processServicesFile($serviceName, $key, $value)
    {
        $services = $this->getServiceFileAsParameters();
        $serviceFileName = $this->directory . $this->serviceFile;

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $defaultValue = $services['parameters'][$serviceName][$key];
        if (is_bool($defaultValue)) {
            $defaultValue = $defaultValue ? 'true' : 'false';
        }

        $result[] = [
            'service-key' => $key,
            'original' => $defaultValue,
            'updated' => $value,
        ];

        $services['parameters'][$serviceName][$key] = $value;

        if (!file_put_contents($serviceFileName, Yaml::encode($services))) {
            $this->getIo()->error(
                sprintf(
                    '%s : %s/services.yml',
                    $this->trans('commands.service.override.messages.error-writing-file'),
                    $this->directory
                )
            );
            return 1;
        }

        return $result;
    }

    private function getServiceFileAsParameters() {

        $serviceFileName = $this->directory . $this->serviceFile;
        if (!file_exists($serviceFileName)) {
            // Copying default services
            $defaultServicesFile = $this->appRoot . '/sites/default/default.services.yml';
            if (!copy($defaultServicesFile, $serviceFileName)) {
                $this->getIo()->error(
                    sprintf(
                        '%s: %s/services.yml',
                        $this->trans('commands.service.override.messages.error-copying-file'),
                        $this->directory
                    )
                );
                return 1;
            }
        }

        return Yaml::decode(file_get_contents($serviceFileName));
    }

    /**
     * Allow to search a specific key to override.
     *
     * @param $service
     * @param null $key
     *
     * @return array
     */
    private function getKeysFromServices($service, $key = null)
    {
        $choiceKey = $this->getIo()->choiceNoList(
            $this->trans('commands.service.override.questions.key'),
            array_keys($service)
        );

        $key = is_null($key) ? $choiceKey:$key.'.'.$choiceKey;

        if(is_array($service[$choiceKey])){
            return $this->getKeysFromConfig($service[$choiceKey], $key);
        }

        return [$key];
    }
}
