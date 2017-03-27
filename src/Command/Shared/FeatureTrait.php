<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\FeatureTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\ConfigurationItem;
use Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite;
use Drupal\config_update\ConfigRevertInterface;

/**
 * Class FeatureTrait
 *
 * @package Drupal\Console\Command
 */
trait FeatureTrait
{
    public function packageQuestion(DrupalStyle $io)
    {
        $packages = $this->getPackagesByBundle($bundle);

        if (empty($packages)) {
            throw new \Exception(
                $this->trans('commands.features.message.no-packages')
            );
        }

        $package = $io->choiceNoList(
            $this->trans('commands.features.import.questions.packages'),
            $packages
        );

        return $package;
    }


    /**
   * @param bool $bundle_name
   *
   * @return \Drupal\features\FeaturesAssignerInterface
   */
    protected function getAssigner($bundle_name)
    {
        /**
         * @var \Drupal\features\FeaturesAssignerInterface $assigner
         */
        $assigner = \Drupal::service('features_assigner');
        if (!empty($bundle_name)) {
            $bundle = $assigner->applyBundle($bundle_name);

            if ($bundle->getMachineName() != $bundle_name) {
            }
        }
        // return configuration for default bundle
        else {
            $assigner->assignConfigPackages();
        }
        return $assigner;
    }

    /**
     * Get a list of features.
     *
     * @param bundle
     *
     * @return array
     */
    protected function getFeatureList($bundle)
    {
        $features = [];
        $manager =  $this->getFeatureManager();
        $modules = $this->getPackagesByBundle($bundle);

        foreach ($modules as $module_name) {
            $feature = $manager->loadPackage($module_name, true);
            $overrides = $manager->detectOverrides($feature);

            $state = $feature->getState();

            if (!empty($overrides) && ($feature->getStatus() != FeaturesManagerInterface::STATUS_NO_EXPORT)) {
                $state = FeaturesManagerInterface::STATE_OVERRIDDEN;
            }

            if ($feature->getStatus() != FeaturesManagerInterface::STATUS_NO_EXPORT) {
                $features[$feature->getMachineName()] = [
                    'name' => $feature->getName(),
                    'machine_name' => $feature->getMachineName(),
                    'bundle_name' => $feature->getBundle(),
                    'status' => $manager->statusLabel($feature->getStatus()),
                    'state' => ($state != FeaturesManagerInterface::STATE_DEFAULT) ? $manager->stateLabel($state) : '',
                ];
            }
        }

        return $features;
    }


    protected function importFeature(DrupalStyle $io, $packages)
    {
        $manager =  $this->getFeatureManager();

        $modules = (is_array($packages)) ? $packages : [$packages];
        $overridden = [] ;
        foreach ($modules as $module_name) {
            $package = $manager->loadPackage($module_name, true);

            if (empty($package)) {
                $io->warning(
                    sprintf(
                        $this->trans('commands.features.import.messages.not-available'),
                        $module_name
                    )
                );
                continue;
            }

            if ($package->getStatus() != FeaturesManagerInterface::STATUS_INSTALLED) {
                $io->warning(
                    sprintf(
                        $this->trans('commands.features.import.messages.uninstall'),
                        $module_name
                    )
                );
                continue;
            }

            $overrides = $manager->detectOverrides($package);
            $missing = $manager->reorderMissing($manager->detectMissing($package));

            if (!empty($overrides) || !empty($missing) && ($package->getStatus() == FeaturesManagerInterface::STATUS_INSTALLED)) {
                $overridden[] = array_merge($missing, $overrides);
            }
        }

        // Process only missing or overridden features
        $components = $overridden;

        if (empty($components)) {
            $io->warning(
                sprintf(
                    $this->trans('commands.features.import.messages.nothing')
                )
            );

            return ;
        } else {
            $this->import($io, $components);
        }
    }

    public function import($io, $components)
    {
        $manager =  $this->getFeatureManager();
        /**
         * @var \Drupal\config_update\ConfigRevertInterface $config_revert
         */
        $config_revert = \Drupal::service('features.config_update');

        $config = $manager->getConfigCollection();

        foreach ($components as $component) {
            foreach ($component as $feature) {
                if (!isset($config[$feature])) {
                    //Import missing component.
                    $item = $manager->getConfigType($feature);
                    $type = ConfigurationItem::fromConfigStringToConfigType($item['type']);
                    $config_revert->import($type, $item['name_short']);
                    $io->info(
                        sprintf(
                            $this->trans('commands.features.import.messages.importing'),
                            $feature
                        )
                    );
                } else {
                    // Revert existing component.
                    $item = $config[$feature];
                    $type = ConfigurationItem::fromConfigStringToConfigType($item->getType());
                    $config_revert->revert($type, $item->getShortName());
                    $io->info(
                        sprintf(
                            $this->trans('commands.features.import.messages.reverting'),
                            $feature
                        )
                    );
                }
            }
        }
    }


    public function getPackagesByBundle($bundle)
    {
        $manager =  $this->getFeatureManager();
        $assigner = $this->getAssigner($bundle);
        $current_bundle = $assigner->getBundle();

        // List all packages availables
        if ($current_bundle->getMachineName() == 'default') {
            $current_bundle = null;
        }

        $packages = array_keys($manager->getFeaturesModules($current_bundle));

        return $packages;
    }

    public function getFeatureManager()
    {
        return  \Drupal::service('features.manager');
    }
}
