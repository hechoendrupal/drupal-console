<?php
/**
 * PHP version 5.5
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace Drupal\Console\Command\Generate\Questions;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\TranslatorManager;
use Exception;
use Symfony\Component\Console\Input\InputInterface;

class AuthenticationProviderQuestions
{
    /** @var DrupalStyle */
    private $io;

    /** @var StringConverter */
    private $string;

    /** @var TranslatorManager */
    private $translator;

    /** @var Manager */
    private $manager;

    /**
     * @param DrupalStyle $io
     * @param TranslatorManager $translator
     * @param Manager $manager
     * @param StringConverter $string
     */
    public function __construct(
        DrupalStyle $io,
        TranslatorManager $translator,
        Manager $manager,
        StringConverter $string
    ) {
        $this->io = $io;
        $this->string = $string;
        $this->translator = $translator;
        $this->manager = $manager;
    }

    /**
     * @param bool $showProfile
     * @return string
     * @throws Exception
     */
    public function askForModule($showProfile = true)
    {
        $modules = $this->manager->showModuleNamesExceptCore();

        if ($showProfile) {
            $modules = array_merge(
                $modules,
                $this->manager->showAllProfileNames()
            );
        }

        if (empty($modules)) {
            throw new Exception(
                'No modules available, execute `generate:module` command to generate one.'
            );
        }

        return $this->io->choiceNoList(
            $this->translator->trans('commands.common.questions.module'),
            $modules
        );
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    public function askForClass()
    {
        $question = $this->translator->trans(
            'commands.generate.authentication.provider.options.class'
        );
        return $this->io->ask(
            $question,
            'DefaultAuthenticationProvider',
            function ($module) { return $this->validateClassName($module); }
        );
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    public function askForProviderId(InputInterface $input)
    {
        $question = $this->translator->trans(
            'commands.generate.authentication.provider.options.provider-id'
        );
        return $this->io->ask(
            $question,
            $this->string->camelCaseToUnderscore($input->getOption('class')),
            function ($provider) { return $this->validateClassName($provider); }
        );
    }

    /**
     * @param string $moduleName
     * @return string
     * @throws Exception
     */
    private function validateClassName($moduleName)
    {
        if (!strlen(trim($moduleName))) {
            throw new Exception('The Class name can not be empty');
        }

        return $this->string->humanToCamelCase($moduleName);
    }
}
