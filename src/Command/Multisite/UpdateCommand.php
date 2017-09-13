<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Multisite\UpdateCommand.
 */

namespace Drupal\Console\Command\Multisite;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class UpdateCommand
 *
 * @package Drupal\Console\Command\Multisite
 */
class UpdateCommand extends Command
{
    protected $appRoot;

    /**
     * DebugCommand constructor.
     *
     * @param $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * @var Filesystem;
     */
    protected $fs;

    /**
     * @var string
     */
    protected $nameOldDirectory = '';

    /**
     * @var array
     */
    protected $explodeOldDirectory = [];

    /**
     * @var string
     */
    protected $nameNewDirectory = '';

    /**
     * @var array
     */
    protected $explodeNewDirectory = [];

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('multisite:update')
            ->setDescription($this->trans('commands.multisite.update.description'))
            ->setHelp($this->trans('commands.multisite.update.help'))
            ->addOption(
                'old-directory',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.multisite.update.options.old-directory')
            )
            ->addOption(
                'new-directory',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.multisite.update.options.new-directory')
            )
            ->addOption(
                'move-settings',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.multisite.update.options.move-settings')
            )
            ->setAliases(['muu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $old_directory = $input->getOption('old-directory');
        if (!$old_directory) {
            $sites = [];

            $multiSiteFile = sprintf(
                '%s/sites/sites.php',
                $this->appRoot
            );

            if (file_exists($multiSiteFile)) {
                include $multiSiteFile;
            }

            if (!$sites) {
                $io->error(
                    $this->trans('commands.debug.multisite.messages.no-multisites')
                );

                return 1;
            }

            $old_directory = $io->choice($this->trans('commands.multisite.update.questions.old-directory'),
                $sites
            );
        }
        $input->setOption('old-directory', $old_directory);

        $new_directory = $input->getOption('new-directory');

        if (!$new_directory) {
            $new_directory = $io->ask($this->trans('commands.multisite.update.questions.new-directory'));
        }
        $input->setOption('new-directory', $new_directory);
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->fs = new Filesystem();

        $multiSiteFile = sprintf(
            '%s/sites/sites.php',
            $this->appRoot
        );

        $sites = [];

        if (file_exists($multiSiteFile)) {
            include $multiSiteFile;
        }

        $this->nameOldDirectory = $sites[$input->getOption('old-directory')];
        $this->nameNewDirectory = $input->getOption('new-directory');
        $this->explodeNewDirectory = explode('/', $this->nameNewDirectory);
        $this->explodeOldDirectory = explode('/', $this->nameOldDirectory);

        if (file_exists($multiSiteFile) && count(explode('/', $this->nameNewDirectory)) <= 2) {
            try {
                $string_to_replace="sites['".$this->explodeOldDirectory[0]."'] = '".$this->nameOldDirectory."';";
                $replace_with="sites['".$this->explodeNewDirectory[0]."'] = '".$this->nameNewDirectory."';";
                $content=file_get_contents($multiSiteFile);
                $content_chunks=explode($string_to_replace, $content);
                $content=implode($replace_with, $content_chunks);
                file_put_contents($multiSiteFile, $content);
            }
            catch (IOExceptionInterface $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.multisite.update.errors.write-fail'),
                        $this->nameOldDirectory,
                        $this->nameNewDirectory
                    )
                );
                return 1;
            }

            if(count($this->explodeNewDirectory) == 1){
                $this->recurse_copy(
                    $this->appRoot.'/sites/'.$this->nameOldDirectory,
                    $this->appRoot.'/sites/'.$this->nameNewDirectory
                );

                if($this->explodeNewDirectory[0] != $this->explodeOldDirectory[0]){
                    $this->fs->remove($this->appRoot.'/sites/'.$this->nameNewDirectory.'/files');

                    $this->recurse_copy(
                        $this->appRoot.'/sites/'.$this->explodeOldDirectory[0].'/files',
                        $this->appRoot.'/sites/'.$this->nameNewDirectory.'/files'
                    );

                    $this->fs->chmod($this->appRoot.'/sites/'.$this->explodeOldDirectory[0], 0755);
                    $this->fs->remove($this->appRoot.'/sites/'.$this->$this->explodeOldDirectory[0]);
                }

                $this->editSettings($io);
            }else{
                if(count($this->explodeOldDirectory) != 1) {
                    if(!$this->fs->exists($this->appRoot.'/sites/'.$this->explodeNewDirectory[0])) {
                        $this->fs->rename(
                            $this->appRoot.'/sites/'.$this->explodeOldDirectory[0],
                            $this->appRoot.'/sites/'.$this->explodeNewDirectory[0]
                        );
                    }

                    if(!$this->fs->exists($this->appRoot.'/sites/'.$this->nameNewDirectory)) {
                        $this->fs->rename(
                            $this->appRoot.'/sites/'.$this->nameOldDirectory,
                            $this->appRoot.'/sites/'.$this->nameNewDirectory
                        );
                    }
                    $this->editSettings($io);

                }else {
                    if(!$this->fs->exists($this->appRoot.'/sites/'.$this->nameNewDirectory)) {
                        try {
                            $this->fs->chmod($this->appRoot.'/sites/'.$this->nameOldDirectory, 0755);
                            $this->fs->mkdir($this->appRoot.'/sites/'.$this->nameNewDirectory, 0755);

                            if($this->explodeOldDirectory[0] != $this->explodeNewDirectory[0]){
                                $this->recurse_copy(
                                    $this->appRoot.'/sites/'.$this->nameOldDirectory,
                                    $this->appRoot.'/sites/'.$this->explodeNewDirectory[0]
                                );
                                $this->fs->remove($this->appRoot.'/sites/'.$this->nameOldDirectory);
                            }
                            $this->editSettings($io);
                        } catch (IOExceptionInterface $e) {
                            $io->error(
                                sprintf(
                                    $this->trans('commands.multisite.update.errors.mkdir-fail'),
                                    $this->nameNewDirectory
                                )
                            );
                        }
                    }else {
                        $io->error(
                            sprintf(
                                $this->trans('commands.multisite.update.errors.subdir-exists'),
                                $this->nameNewDirectory
                            )
                        );
                    }
                }

                $this->moveSettings($io, $input->getOption('move-settings'));
            }

        }else {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.update.errors.invalid-new-dir'),
                    $this->nameNewDirectory
                )
            );
        }

    }

    protected function moveSettings(DrupalStyle $io, $moveSettings)
    {
        if ($moveSettings) {
            try {
                if(!$this->fs->exists($this->appRoot.'/sites/'.$this->nameNewDirectory.'/settings.php')){
                    $this->fs->copy(
                        $this->appRoot.'/sites/'.$this->explodeNewDirectory[0].'/settings.php',
                        $this->appRoot.'/sites/'.$this->nameNewDirectory.'/settings.php'
                    );
                    $this->fs->remove($this->appRoot.'/sites/'.$this->explodeNewDirectory[0].'/settings.php');
                }
            } catch (IOExceptionInterface $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.multisite.update.errors.copy-fail'),
                        $this->appRoot.'/sites/'.$this->explodeNewDirectory[0].'/settings.php',
                        $this->nameNewDirectory.'/settings.php'
                    )
                );
                return 1;
            }
$this->fs->
            $io->success(
                sprintf(
                    $this->trans('commands.multisite.update.messages.move-settings'),
                    $this->nameNewDirectory
                )
            );
        }
    }

    protected function editSettings(DrupalStyle $io)
    {
        $multiSiteSettingsFile = sprintf(
            '%s/sites/'.$this->explodeNewDirectory[0].'/settings.php',
            $this->appRoot
        );
        if(!$this->fs->exists($multiSiteSettingsFile)){
            $multiSiteSettingsFile = sprintf(
                '%s/sites/'.$this->nameNewDirectory.'/settings.php',
                $this->appRoot
            );
        }


        $databases = [];
        $config_directories = [];

        if (file_exists($multiSiteSettingsFile)) {
            include $multiSiteSettingsFile;
        }

        try {
            //Replace $databases['default']['default']['database']
            $line = explode('/', $databases['default']['default']['database']);
            $string_to_replace= $databases['default']['default']['database'];
            $replace_with="sites/".$this->explodeNewDirectory[0]."/files/".end($line);
            $content=file_get_contents($multiSiteSettingsFile);
            $content_chunks=explode($string_to_replace, $content);
            $content=implode($replace_with, $content_chunks);
            echo PHP_EOL;
            echo PHP_EOL;
            var_dump($string_to_replace);
            var_dump($replace_with);
            echo PHP_EOL;
            echo PHP_EOL;
            //Replace $config_directories['sync']
            $string_to_replace= $config_directories['sync'];
            $replace_with=str_replace($this->nameOldDirectory, $this->nameNewDirectory, $config_directories['sync']);
            $content_chunks=explode($string_to_replace, $content);
            $content=implode($replace_with, $content_chunks);
            file_put_contents($multiSiteSettingsFile, $content);
            var_dump($string_to_replace);
            var_dump($replace_with);
        }
        catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    'no se pudo escribir settings'
                )
            );
            return 1;
        }

    }

    public function recurse_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

}
