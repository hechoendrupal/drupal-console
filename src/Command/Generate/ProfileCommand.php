<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ProfileCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Generator\ProfileGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Webmozart\PathUtil\Path;

/**
 * Class ProfileCommand
 *
 * @package Drupal\Console\Command\Generate
 */

class ProfileCommand extends Command
{
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var ProfileGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * ProfileCommand constructor.
     *
     * @param Manager          $extensionManager
     * @param ProfileGenerator $generator
     * @param StringConverter  $stringConverter
     * @param Validator        $validator
     * @param $appRoot
     */
    public function __construct(
        Manager $extensionManager,
        ProfileGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        $appRoot
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:profile')
            ->setDescription($this->trans('commands.generate.profile.description'))
            ->setHelp($this->trans('commands.generate.profile.help'))
            ->addOption(
                'profile',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.profile')
            )
            ->addOption(
                'machine-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.machine-name')
            )
            ->addOption(
                'profile-path',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.profile.options.profile-path')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.description')
            )
            ->addOption(
                'core',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.core')
            )
            ->addOption(
                'dependencies',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.dependencies'),
                ''
            )
            ->addOption(
                'themes',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.themes'),
                ''
            )
            ->addOption(
                'distribution',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.profile.options.distribution')
            )->setAliases(['gpr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        // Get the profile path and define a profile path if it is null
        // Check that it is an absolute path or otherwise create an absolute path using appRoot
        $profile_path = $input->getOption('profile-path');
        $profile_path = $profile_path == null ? 'profiles' : $profile_path;
        $profile_path = Path::isAbsolute($profile_path) ? $profile_path : Path::makeAbsolute($profile_path, $this->appRoot);
        $profile_path = $this->validator->validateModulePath($profile_path, true);

        $profile = $this->validator->validateModuleName($input->getOption('profile'));
        $machine_name = $this->validator->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $core = $input->getOption('core');
        $dependencies = $this->validator->validateExtensions($input->getOption('dependencies'), 'module', $this->getIo());
        $themes = $this->validator->validateExtensions($input->getOption('themes'), 'theme', $this->getIo());
        $distribution = $input->getOption('distribution');

        $this->generator->generate([
            'profile' => $profile,
            'machine_name' => $machine_name,
            'type' => 'profile',
            'core' => $core,
            'description' => $description,
            'dependencies' => $dependencies,
            'themes' => $themes,
            'distribution' => $distribution,
            'dir' => $profile_path,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        //$stringUtils = $this->getStringHelper();
        $validators = $this->validator;

        try {
            // A profile is technically also a module, so we can use the same
            // validator to check the name.
            $profile = $input->getOption('profile') ? $validators->validateModuleName($input->getOption('profile')) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());

            return 1;
        }

        if (!$profile) {
            $profile = $this->getIo()->ask(
                $this->trans('commands.generate.profile.questions.profile'),
                '',
                function ($profile) use ($validators) {
                    return $validators->validateModuleName($profile);
                }
            );
            $input->setOption('profile', $profile);
        }

        try {
            $machine_name = $input->getOption('machine-name') ? $validators->validateModuleName($input->getOption('machine-name')) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());

            return 1;
        }

        if (!$machine_name) {
            $machine_name = $this->getIo()->ask(
                $this->trans('commands.generate.profile.questions.machine-name'),
                $this->stringConverter->createMachineName($profile),
                function ($machine_name) use ($validators) {
                    return $validators->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machine_name);
        }

        $profile_path = $input->getOption('profile-path');
        if (!$profile_path) {
            $profile_path = $this->getIo()->ask(
                $this->trans('commands.generate.profile.questions.profile-path'),
                'profiles',
                function ($profile_path) use ($machine_name) {
                    $fullPath = Path::isAbsolute($profile_path) ? $profile_path : Path::makeAbsolute($profile_path, $this->appRoot);
                    $fullPath = $fullPath.'/'.$machine_name;
                    if (file_exists($fullPath)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.profile.errors.directory-exists'),
                                $fullPath
                            )
                        );
                    }

                    return $profile_path;
                }
            );
        }
        $input->setOption('profile-path', $profile_path);

        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.profile.questions.description'),
                $this->trans('commands.generate.profile.suggestions.my-useful-profile')
            );
            $input->setOption('description', $description);
        }

        $core = $input->getOption('core');
        if (!$core) {
            $core = $this->getIo()->ask(
                $this->trans('commands.generate.profile.questions.core'),
                '8.x'
            );
            $input->setOption('core', $core);
        }

        $dependencies = $input->getOption('dependencies');
        if (!$dependencies) {
            if ($this->getIo()->confirm(
                $this->trans('commands.generate.profile.questions.dependencies'),
                true
            )
            ) {
                $dependencies = $this->getIo()->ask(
                    $this->trans('commands.generate.profile.options.dependencies'),
                    ''
                );
            }
            $input->setOption('dependencies', $dependencies);
        }

        $distribution = $input->getOption('distribution');
        if (!$distribution) {
            if ($this->getIo()->confirm(
                $this->trans('commands.generate.profile.questions.distribution'),
                false
            )
            ) {
                $distribution = $this->getIo()->ask(
                    $this->trans('commands.generate.profile.options.distribution'),
                    $this->trans('commands.generate.profile.suggestions.my-kick-ass-distribution')
                );
                $input->setOption('distribution', $distribution);
            }
        }
    }
}
