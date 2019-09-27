<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ThemeSettingGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 *
 */
class ThemeSettingGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $dir = $parameters['theme_path'];
        $theme = $parameters['theme'];
        $ymlFile = new Parser();
        $filesystem = new Filesystem();
        $file_path = $dir.'/config/install/'.$theme.'.settings.yml';
        $yaml = new Parser();
        $data_yml = $this->arrData($parameters);
        if ($filesystem->exists($dir)) {
            if ($filesystem->exists($file_path)) {
                $yaml_parsed = $yaml->parse(file_get_contents($file_path));
                if (!empty($yaml_parsed) && $parameters['merge-existing-file']) {
                    $file_yaml = array();
                    $file_yaml = array_replace_recursive($yaml_parsed, $data_yml);
                    $this->saveData($filesystem, $file_path, $file_yaml);
                } elseif (empty($yaml_parsed)) {
                    $this->saveData($filesystem, $file_path, $data_yml);
                }
                return 0;
            } else {
                $this->saveData($filesystem, $file_path, $data_yml);
                return 0;    
            }
        } else {
            return 1;
        }
    }

    
    /**
     * {@inheritdoc}
     */
    public function createFolders($dir)
    {
        try {
            $filesystem = new Filesystem();
            $filesystem->mkdir($dir.'/config');
            $filesystem->mkdir($dir.'/config/install');
        } catch (IOExceptionInterface $e) {
            $this->getIo()->error('An error occurred while creating your directory at: "%s"', ' '.$e->getPath());
            return 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function arrData($parameters)
    {
        return [
            'favicon' => [
                'mimetype' => 'image/vnd.microsoft.icon',
                'path' => '',
                'url' => '',
                'use_default' => $parameters['favicon'], 
            ],
            'features' => [
                'comment_user_picture' => $parameters['commentUserPicture'],
                'comment_user_verification' => $parameters['commentUserVerification'],
                'favicon' => $parameters['favicon'],
                'node_user_picture' => $parameters['nodeUserPicture'],
            ],
            'logo' => [
                'path' => '',
                'url' => '',
                'use_default' => $parameters['logo'],
            ],
        ];   
    }

    /**
     * {@inheritdoc}
     */
    public function saveData($filesystem, $file_path, $data_yml)
    {
        $yaml = new Parser();
        $dumper = new Dumper();
        try {
            $yaml = $dumper->dump($data_yml, 10);
        } catch (\Exception $e) {
            $this->getIo()->error(
                sprintf(
                    '%s: %s',
                    'Error on yml',
                    $e->getMessage()
                )
            );
            return;
        }
        try {
            file_put_contents($file_path, $yaml);
        } catch (\Exception $e) {
            $this->getIo()->error(
                sprintf(
                    '%s: %s',
                    'Error saving the file',
                    $e->getMessage()
                )
            );
            return;
        }

        $this->getIo()->success(
            sprintf(
                'The file is on: %s',
                $file_path
            )
        );
    }

}
