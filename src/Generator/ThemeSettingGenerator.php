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
use Symfony\Component\Yaml\Parser;

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
        $file_path = $dir.'/config/install/'.$theme.'.setting.yml';
        if ($filesystem->exists($dir)) {
            $data = [
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
            if ($filesystem->exists($file_path)) {
                $arr_conf = Yaml::decode($file_path);
                $data = array_merge_recursive($data, $arr_conf);
            }
            try {
                $filesystem->mkdir($dir.'/config');
                $filesystem->mkdir($dir.'/config/install');
                $data_yml = Yaml::encode($data);
                $filesystem->dumpFile($file_path, $data_yml);
            } catch (IOExceptionInterface $e) {
                $this->getIo()->error($this->trans('commands.config.edit.messages.no-directory').' '.$e->getPath());
                return 1;
            }
            return 0;   
        } else {
            return 1;
        }
    }
}
