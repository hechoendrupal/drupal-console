<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\AuthenticationProviderCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Style\DrupalStyle;

class AuthenticationProviderCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:authentication:provider')
            ->setDescription($this->trans('commands.generate.authentication.provider.description'))
            ->setHelp($this->trans('commands.generate.authentication.provider.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.authentication.provider.options.class')
            )
            ->addOption(
                'provider-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.authentication.provider.options.provider-id')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $class = $input->getOption('class');
        $provider_id = $input->getOption('provider-id');

        $this->getGenerator()
            ->generate($module, $class, $provider_id);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $stringUtils = $this->getStringHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $output->ask(
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
            $provider_id = $output->ask(
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

    protected function createGenerator()
    {
        return new AuthenticationProviderGenerator();
    }
}
