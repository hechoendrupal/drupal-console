<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\AuthenticationProviderCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Generate\Questions\AuthenticationProviderQuestions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Generator\AuthenticationProviderGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Extension\Manager;

class AuthenticationProviderCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;
    use CommandTrait;

    /** @var Manager  */
    protected $extensionManager;

    /** @var AuthenticationProviderGenerator  */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * AuthenticationProviderCommand constructor.
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
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $class = $input->getOption('class');
        $providerId = $input->getOption('provider-id');

        $this->generator->generate($module, $class, $providerId);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = new AuthenticationProviderQuestions(
            new DrupalStyle($input, $output),
            $this->translator,
            $this->extensionManager,
            $this->stringConverter
        );

        $module = $input->getOption('module');
        if (!$module) {
            $questions->askForModule($input);
        }

        $class = $input->getOption('class');
        if (!$class) {
            $questions->askForClass($input);
        }

        $providerId = $input->getOption('provider-id');
        if (!$providerId) {
            $questions->askForProviderId($input);
        }
    }
}
