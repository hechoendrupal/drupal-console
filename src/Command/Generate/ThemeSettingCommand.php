<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ThemeSettingCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\ThemeSettingGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ThemeHandler;
use Webmozart\PathUtil\Path;
use Drupal\Console\Command\Shared\ThemeTrait;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Console\Core\Style\DrupalStyle;


/**
 * Class ThemeSettingCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class ThemeSettingCommand extends Command
{
    use ConfirmationTrait;
    use ArrayInputTrait;
    use ThemeTrait;

    /**
     * @var Manager
    */
    protected $extensionManager;

    /**
     * @var ThemeSettingGenerator
    */
    protected $generator;

    /**
    * @var Validator
    */
    protected $validator;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * ThemeSettingCommand constructor.
     *
     * @param Manager                $extensionManager
     * @param ThemeSettingGenerator         $generator
     * @param Validator              $validator
     * @param $appRoot
     * @param ThemeHandler           $themeHandler
     * @param Site                   $site
     * @param StringConverter        $stringConverter
     */
    public function __construct(
        Manager $extensionManager,
        ThemeSettingGenerator $generator,
        Validator $validator,
        $appRoot,
        ThemeHandler $themeHandler,
        Site $site,
        StringConverter $stringConverter
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        $this->themeHandler = $themeHandler;
        $this->site = $site;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:theme:setting')
            ->setDescription($this->trans('commands.generate.theme.setting.description'))
            ->setHelp($this->trans('commands.generate.theme.setting.help'))
            ->addOption(
                'theme',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.setting.options.theme')
            )
            ->addOption(
                'theme-path',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.setting.options.theme-path')
            )
            ->addOption(
                'favicon',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.setting.options.favicon')
            )
            ->addOption(
                'comment-user-picture',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.setting.options.comment-user-picture')
            )
            ->addOption(
                'comment-user-verification',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.setting.options.comment-user-verification')
            )
            ->addOption(
                'node-user-picture',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.setting.options.node-user-picture')
            )
            ->addOption(
                'logo',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.setting.options.logo')
            )
            ->addOption(
                'merge-existing-file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.setting.options.merge-existing-file')
            )
            ->setAliases(['gts']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = $this->validator->validateModuleName($input->getOption('theme'));
        $theme_path = $input->getOption('theme-path');
        if (is_null($theme_path)) {
            $uri = $this->site->getMultisiteName($input);
            $defaultThemePath = 'themes/custom';
            $theme_path = $this->site->multisiteMode($uri)? 'sites/'.$this->site->getMultisiteDir($uri).'/'.$defaultThemePath : $defaultThemePath;
        }
        $theme_path = Path::isAbsolute($theme_path) ? $theme_path : Path::makeAbsolute($theme_path, $this->appRoot);
        $theme_path = $this->validator->validateModulePath($theme_path, true);

        $favicon = $input->getOption('favicon');
        $commentUserPicture = $input->getOption('comment-user-picture');
        $commentUserVerification = $input->getOption('comment-user-verification');
        $nodeUserPicture = $input->getOption('node-user-picture');
        $logo = $input->getOption('logo');
        $mergeExistingFile = $input->getOption('merge-existing-file');
        $this->generator->setIo($this->getIo());
        return $this->generator->generate( 
            [
            'theme' => $theme,
            'theme_path' => $theme_path,
            'favicon' => $favicon,
            'commentUserPicture' => $commentUserPicture,
            'commentUserVerification' => $commentUserVerification,
            'nodeUserPicture' => $nodeUserPicture,
            'logo' => $logo,
            'merge-existing-file' => (bool)$mergeExistingFile
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --theme option
        try {
            $theme = $input->getOption('theme') ? $this->validator->validateModuleName($input->getOption('theme')) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());
            return 1;   
        }
        if (!$theme) {
            // @see Drupal\Console\Command\Shared\ThemeTrait::themeQuestion
            $theme = $this->themeQuestion();
            $theme_list = $this->extensionManager->discoverThemes()
            ->showInstalled()
            ->showNoCore()
            ->getList();
            $input->setOption('theme', $theme);
            
        }

        // --theme-path option
        $theme_path = $input->getOption('theme-path');
        if (!$theme_path) {
            $theme_path = $this->appRoot.'/'.$theme_list[$theme]->getPath();
            $input->setOption('theme-path', $theme_path);
        }

        // --favicon option
        $favicon = $input->getOption('favicon');
        if (!$favicon) {
            $favicon = $this->getIo()->choice(
                $this->trans('commands.generate.theme.setting.questions.favicon'),
                ['true', 'false'],
                'true'
            );
            $input->setOption('favicon', $favicon);
        }

        // --comment-user-picture option
        $commentUserPicture = $input->getOption('comment-user-picture');
        if (!$commentUserPicture) {
            $commentUserPicture = $this->getIo()->choice(
                $this->trans('commands.generate.theme.setting.questions.comment-user-picture'),
                ['true', 'false'],
                'true'
            );
            $input->setOption('comment-user-picture', $commentUserPicture);
        }

        // --comment-user-verification option
        $commentUserVerification = $input->getOption('comment-user-verification');
        if (!$commentUserVerification) {
            $commentUserVerification = $this->getIo()->choice(
                $this->trans('commands.generate.theme.setting.questions.comment-user-verification'),
                ['true', 'false'],
                'true'
            );
            $input->setOption('comment-user-verification', $commentUserVerification);
        }

        // --node-user-picture option
        $nodeUserPicture = $input->getOption('node-user-picture');
        if (!$nodeUserPicture) {
            $nodeUserPicture = $this->getIo()->choice(
                $this->trans('commands.generate.theme.setting.questions.node-user-picture'),
                ['true', 'false'],
                'true'
            );
            $input->setOption('node-user-picture', $nodeUserPicture);
        }

        // --logo option
        $logo = $input->getOption('logo');
        if (!$logo) {
            $logo = $this->getIo()->choice(
                $this->trans('commands.generate.theme.setting.questions.logo'),
                ['true', 'false'],
                'true'
            );
            $input->setOption('logo', $logo);
        }

        // --merge-existing-file
        $mergeExistingFile = $input->getOption('merge-existing-file');
        if (!$mergeExistingFile) {
            $file_path = $theme_path.'/config/install/'.$theme.'.settings.yml';
            $filesystem = new Filesystem();
            if ($filesystem->exists($file_path)) {
                $data_cont = file_get_contents($file_path);
                if (strlen($data_cont)>0) {
                    $mergeExistingFile = $this->getIo()->choice(
                        $this->trans('commands.generate.theme.setting.questions.merge-existing-file'),
                        ['true', 'false'],
                        'true'
                    );
                    $input->setOption('merge-existing-file', $mergeExistingFile);
                } else {
                    $input->setOption('merge-existing-file', 'false');
                }
            } else {
                $input->setOption('merge-existing-file', 'false');
            }
        } else {
            $input->setOption('merge-existing-file', 'false');
        }
        $io = new DrupalStyle($input, $output);
        
    }
}
