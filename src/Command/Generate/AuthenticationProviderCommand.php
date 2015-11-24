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
        $dialog = $this->getDialogHelper();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
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
        $dialog = $this->getDialogHelper();

        $stringUtils = $this->getStringHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.authentication.provider.options.class'),
                    'DefaultAuthenticationProvider'
                ),
                function ($value) use ($stringUtils) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }

                    return $stringUtils->humanToCamelCase($value);
                },
                false,
                'DefaultAuthenticationProvider'
            );
        }

        // --provider-id option
        $provider_id = $input->getOption('provider-id');
        if (!$provider_id) {
            $provider_id = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.authentication.provider.options.provider-id'),
                    $stringUtils->camelCaseToUnderscore($class)
                ),
                function ($value) use ($stringUtils) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }

                    return $stringUtils->camelCaseToUnderscore($value);
                },
                false,
                $stringUtils->camelCaseToUnderscore($class)
            );
        }

        $input->setOption('class', $class);
        $input->setOption('provider-id', $provider_id);
    }

    protected function createGenerator()
    {
        return new AuthenticationProviderGenerator();
    }
}
