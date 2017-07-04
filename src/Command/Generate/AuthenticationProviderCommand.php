<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\AuthenticationProviderCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;

class AuthenticationProviderCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var AuthenticationProviderGenerator
*/
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;


    /**
     * AuthenticationProviderCommand constructor.
     *
     * @param Manager                         $extensionManager
     * @param AuthenticationProviderGenerator $generator
     * @param StringConverter                 $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        AuthenticationProviderGenerator $generator,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:authentication:provider')
            ->setDescription($this->trans('commands.generate.authentication.provider.description'))
            ->setHelp($this->trans('commands.generate.authentication.provider.help'))
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.authentication.provider.options.class')
            )
            ->addOption(
                'provider-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.authentication.provider.options.provider-id')
            )
            ->setAliases(['gap']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $module = $input->getOption('module');
        $class = $input->getOption('class');
        $provider_id = $input->getOption('provider-id');

        $this->generator->generate($module, $class, $provider_id);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $stringUtils = $this->stringConverter;

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans(
                    'commands.generate.authentication.provider.options.class'
                ),
                'DefaultAuthenticationProvider',
                function ($value) use ($stringUtils) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }

                    return $stringUtils->humanToCamelCase($value);
                }
            );
            $input->setOption('class', $class);
        }
        // --provider-id option
        $provider_id = $input->getOption('provider-id');
        if (!$provider_id) {
            $provider_id = $io->ask(
                $this->trans('commands.generate.authentication.provider.options.provider-id'),
                $stringUtils->camelCaseToUnderscore($class),
                function ($value) use ($stringUtils) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }

                    return $stringUtils->camelCaseToUnderscore($value);
                }
            );
            $input->setOption('provider-id', $provider_id);
        }
    }
}
