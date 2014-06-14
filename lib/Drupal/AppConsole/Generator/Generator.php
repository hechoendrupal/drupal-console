<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\AppConsole\Generator;

use Drupal\AppConsole\Utils\Utils;

class Generator {
  private $skeletonDirs;

  /**
   * Sets an array of directories to look for templates.
   *
   * The directories must be sorted from the most specific to the most
   * directory.
   *
   * @param array $skeletonDirs An array of skeleton dirs
   */
  public function setSkeletonDirs($skeletonDirs) {
    $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
  }

  protected function render($template, $parameters) {
    $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
      'debug'            => true,
      'cache'            => false,
      'strict_variables' => true,
      'autoescape'       => false,
    ));

    $twig->addFunction($this->getServiceAsParamater());
    $twig->addFunction($this->getServiceAsParamaterKeys());

    return $twig->render($template, $parameters);
  }

  protected function renderFile($template, $target, $parameters, $flag=null) {
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }
    return file_put_contents($target, $this->render($template, $parameters), $flag);
  }

  public function camelCaseToUnderscore($camel_case){
    return Utils::camelCaseToUnderscore($camel_case);
  }

  public function getServiceAsParamater() {
      $servicesAsParameters = new \Twig_SimpleFunction('servicesAsParameters', function ($services) {
        $parameters = [];
        foreach ($services as $service) {
          $parameters[] = sprintf('%s $%s', $service['short'], $service['machine_name']);
        }
        return $parameters;
      });
      return $servicesAsParameters;
  }

  public function getServiceAsParamaterKeys() {
    $servicesAsParametersKeys = new \Twig_SimpleFunction('servicesAsParametersKeys', function ($services) {
      $parameters = [];
      foreach ($services as $service) {
        $parameters[] = sprintf('"@%s"', $service['name']);
      }
      return $parameters;
    });
    return $servicesAsParametersKeys;
  }

}
