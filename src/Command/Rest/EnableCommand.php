<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Rest\EnableCommand.
 */

namespace Drupal\Console\Command\Rest;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\RestTrait;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\Core\Authentication\AuthenticationCollector;
use Drupal\Core\Config\ConfigFactory;

/**
 * @DrupalCommand(
 *     extension = "rest",
 *     extensionType = "module"
 * )
 */
class EnableCommand extends Command
{
    use CommandTrait;
    use RestTrait;

    /**
     * @var ResourcePluginManager $pluginManagerRest
     */
    protected $pluginManagerRest;

    /**
     * @var AuthenticationCollector $authenticationCollector
     */
    protected $authenticationCollector;

    /**
     * @var ConfigFactory  
     */
    protected $configFactory;

    /**
     * The available serialization formats.
     *
     * @var array
     */
    protected $formats;

    /**
     * EnableCommand constructor.
     * @param ResourcePluginManager   $pluginManagerRest
     * @param AuthenticationCollector $authenticationCollector
     * @param ConfigFactory           $configFactory
     * @param array $formats
     *   The available serialization formats.
     */
    public function __construct(
        ResourcePluginManager $pluginManagerRest,
        AuthenticationCollector $authenticationCollector,
        ConfigFactory $configFactory,
        array $formats
    ) {
        $this->pluginManagerRest = $pluginManagerRest;
        $this->authenticationCollector = $authenticationCollector;
        $this->configFactory = $configFactory;
        $this->formats = $formats;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('rest:enable')
            ->setDescription($this->trans('commands.rest.enable.description'))
            ->addArgument(
                'resource-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.rest.debug.arguments.resource-id')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $resource_id = $input->getArgument('resource-id');
        $rest_resources = $this->getRestResources();

        $rest_resources_ids = array_merge(
            array_keys($rest_resources['enabled']),
            array_keys($rest_resources['disabled'])
        );

        if (!$resource_id) {
            $resource_id = $io->choiceNoList(
                $this->trans('commands.rest.enable.arguments.resource-id'),
                $rest_resources_ids
            );
        }

        $this->validateRestResource(
            $resource_id,
            $rest_resources_ids,
            $this->translator
        );
        $input->setArgument('resource-id', $resource_id);

        // Calculate states available by resource and generate the question
        $plugin = $this->pluginManagerRest->getInstance(['id' => $resource_id]);

        $methods = $plugin->availableMethods();
        $method = $io->choice(
            $this->trans('commands.rest.enable.arguments.method'),
            $methods
        );
        $io->writeln(
            $this->trans('commands.rest.enable.messages.selected-method') . ' ' . $method
        );

        $format = $io->choice(
            $this->trans('commands.rest.enable.arguments.formats'),
            $this->formats
        );
        $io->writeln(
            $this->trans('commands.rest.enable.messages.selected-format') . ' ' . $format
        );

        // Get Authentication Provider and generate the question
        $authenticationProviders = $this->authenticationCollector->getSortedProviders();

        $authenticationProvidersSelected = $io->choice(
            $this->trans('commands.rest.enable.messages.authentication-providers'),
            array_keys($authenticationProviders),
            0,
            true
        );

        $io->writeln(
            $this->trans('commands.rest.enable.messages.selected-authentication-providers').' '.implode(
                ', ',
                $authenticationProvidersSelected
            )
        );

        $rest_settings = $this->getRestDrupalConfig();

        $rest_settings[$resource_id][$state]['supported_formats'] = $format;
        $rest_settings[$resource_id][$state]['supported_auth'] = $authenticationProvidersSelected;
        $config = $this->configFactory->getEditable('rest.settings');
        $config->set('resources', $rest_settings);
        $config->save();

        return 0;
    }
}
