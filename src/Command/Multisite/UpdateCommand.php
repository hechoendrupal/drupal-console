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
    protected $uri = '';

    /**
     * @var string
     */
    protected $directory = '';

    /**
     * @var array
     */
    protected $explodeUriDirectory = [];

    /**
     * @var array
     */
    protected $explodeDirectory = [];

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('multisite:update')
            ->setDescription($this->trans('commands.multisite.update.description'))
            ->setHelp($this->trans('commands.multisite.update.help'))
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.multisite.update.options.directory')
            )
            ->setAliases(['muu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->uri = parse_url($input->getParameterOption(['--uri', '-l'], 'default'), PHP_URL_HOST);

        $sites = $this->getMultisite($io, $this->uri);
        if ($this->uri == "default") {
            $this->uri = $io->choice($this->trans('commands.multisite.update.questions.uri'),
                $sites
            );
        }else if (!array_key_exists($this->uri, $sites)) {
            $io->error(
                $this->trans('commands.multisite.update.error.invalid-uri')
            );

            return 1;
        }
        $this->uri = $sites[$this->uri];

        $directory = $input->getOption('directory');
        if (!$directory) {
            $directory = $io->ask($this->trans('commands.multisite.update.questions.directory'));
        }
        $input->setOption('directory', $directory);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->fs = new Filesystem();

        if(empty($this->uri)){
            $uri =  parse_url($input->getParameterOption(['--uri', '-l'], 'default'), PHP_URL_HOST);
            $sites = $this->getMultisite($io, $uri);
            $this->uri = $sites[$uri];
        }

        $this->directory = $input->getOption('directory');
        $this->explodeDirectory = explode('/', $this->directory);
        $this->explodeUriDirectory = explode('/', $this->uri);

        if (count($this->explodeDirectory) <= 2) {
            try {
                $multiSiteFile = sprintf(
                    '%s/sites/sites.php',
                    $this->appRoot
                );
                //Replace Multisite name in sites/sites.php
                $string_to_replace="sites['".$this->explodeUriDirectory[0]."'] = '".$this->uri."';";
                $replace_with="sites['".$this->explodeDirectory[0]."'] = '".$this->directory."';";
                $content=file_get_contents($multiSiteFile);
                $content_chunks=explode($string_to_replace, $content);
                $content=implode($replace_with, $content_chunks);
                file_put_contents($multiSiteFile, $content);
            }
            catch (IOExceptionInterface $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.multisite.update.errors.write-fail'),
                        $this->uri,
                        $this->directory
                    )
                );
                return 1;
            }

            //Directory == 1 folder
            if(count($this->explodeDirectory) == 1){

                $this->recurse_copy(
                    $this->appRoot.'/sites/'.$this->uri,
                    $this->appRoot.'/sites/'.$this->directory
                );

                if($this->explodeDirectory[0] != $this->explodeUriDirectory[0]
                    && $this->fs->exists($this->appRoot.'/sites/'.$this->explodeUriDirectory[0].'/files')
                ){
                    $this->fs->remove($this->appRoot.'/sites/'.$this->directory.'/files');

                    $this->recurse_copy(
                        $this->appRoot.'/sites/'.$this->explodeUriDirectory[0].'/files',
                        $this->appRoot.'/sites/'.$this->directory.'/files'
                    );
                }

                $this->fs->chmod($this->appRoot.'/sites/'.$this->uri, 0755);
                $this->fs->remove($this->appRoot.'/sites/'.$this->uri);
            }else{
                //Directory == 2 folders && uri == 2 folders
                if(count($this->explodeUriDirectory) != 1) {
                    if (!$this->fs->exists($this->appRoot . '/sites/' . $this->directory)) {
                        $this->fs->rename(
                            $this->appRoot . '/sites/' . $this->uri,
                            $this->appRoot . '/sites/' . $this->directory
                        );
                    }

                    if(count(scandir($this->appRoot.'/sites/'.$this->explodeUriDirectory[0])) != 2){
                        $this->recurse_copy(
                            $this->appRoot.'/sites/'.$this->explodeUriDirectory[0].'/files',
                            $this->appRoot . '/sites/' . $this->explodeDirectory[0].'/files'
                        );
                    }else {
                        $this->fs->remove($this->appRoot.'/sites/'.$this->explodeUriDirectory[0]);
                    }
                }
                //Directory == 2 folders && uri == 1 folder
                else {
                    if(!$this->fs->exists($this->appRoot.'/sites/'.$this->directory)) {
                        try {
                            $this->fs->chmod($this->appRoot.'/sites/'.$this->uri, 0755);
                            $this->fs->mkdir($this->appRoot.'/sites/'.$this->directory, 0755);

                            if($this->explodeUriDirectory[0] != $this->explodeDirectory[0]){
                                $this->recurse_copy(
                                    $this->appRoot.'/sites/'.$this->uri,
                                    $this->appRoot.'/sites/'.$this->explodeDirectory[0]
                                );
                                $this->fs->remove($this->appRoot.'/sites/'.$this->uri);
                            }
                        } catch (IOExceptionInterface $e) {
                            $io->error(
                                sprintf(
                                    $this->trans('commands.multisite.update.errors.mkdir-fail'),
                                    $this->directory
                                )
                            );
                        }
                    }
                    $this->moveSettings($io);
                }
            }

            $this->editSettings($io);

        }else {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.update.errors.invalid-new-dir'),
                    $this->directory
                )
            );
        }

    }

    /**
     * Get all Multisites.
     *
     * @param DrupalStyle $io
     */
    protected function getMultisite(DrupalStyle $io)
    {
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

        return $sites;
    }

    /**
     * Move the settings.php file to new directory.
     *
     * @param DrupalStyle $io
     */
    protected function moveSettings(DrupalStyle $io)
    {
        try {
            if(!$this->fs->exists($this->appRoot.'/sites/'.$this->directory.'/settings.php')){
                $this->fs->copy(
                    $this->appRoot.'/sites/'.$this->uri.'/settings.php',
                    $this->appRoot.'/sites/'.$this->directory.'/settings.php'
                );
                $this->fs->remove($this->appRoot.'/sites/'.$this->uri.'/settings.php');
            }
        } catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.update.errors.copy-fail'),
                    $this->appRoot.'/sites/'.$this->explodeDirectory[0].'/settings.php',
                    $this->directory.'/settings.php'
                )
            );
            return 1;
        }
    }

    /**
     * Edit the settings.php file to change the database parameters, because the settings.php file was moved.
     *
     * @param DrupalStyle $io
     */
    protected function editSettings(DrupalStyle $io)
    {
        $multiSiteSettingsFile = sprintf(
            '%s/sites/'.$this->explodeDirectory[0].'/settings.php',
            $this->appRoot
        );
        if(!$this->fs->exists($multiSiteSettingsFile)){
            $multiSiteSettingsFile = sprintf(
                '%s/sites/'.$this->directory.'/settings.php',
                $this->appRoot
            );
        }

        $databases = [];
        $config_directories = [];

        if (file_exists($multiSiteSettingsFile)) {
            include $multiSiteSettingsFile;
        }

        try {
            if (!empty($databases) || !empty($config_directories) ) {
                //Replace $databases['default']['default']['database']
                $line = explode('/', $databases['default']['default']['database']);
                $string_to_replace= $databases['default']['default']['database'];
                $replace_with="sites/".$this->explodeDirectory[0]."/files/".end($line);
                $content=file_get_contents($multiSiteSettingsFile);
                $content_chunks=explode($string_to_replace, $content);
                $content=implode($replace_with, $content_chunks);

                //Replace $config_directories['sync']
                $string_to_replace= $config_directories['sync'];
                $replace_with=str_replace($this->uri, $this->directory, $config_directories['sync']);
                $content_chunks=explode($string_to_replace, $content);
                $content=implode($replace_with, $content_chunks);
                file_put_contents($multiSiteSettingsFile, $content);
            }
        }
        catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.multisite.update.messages.write-fail'),
                    $multiSiteSettingsFile
                )
            );
            return 1;
        }

    }

    /**
     * Custom function to recursively copy all file and folders in new destination
     *
     * @param   $source
     * @param   $destination
     */
    public function recurse_copy($source, $destination) {
        $directory = opendir($source);
        $this->fs->mkdir($destination, 0755);
        while(false !== ( $file = readdir($directory)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($source . '/' . $file) ) {
                    $this->recurse_copy($source . '/' . $file,$destination . '/' . $file);
                }
                else {
                    $this->fs->copy($source . '/' . $file,$destination . '/' . $file);
                }
            }
        }
        closedir($directory);
    }

}
