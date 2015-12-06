<?php

/**
 * @file
 * Contains \Drupal\Console\Command\SelfUpdateCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Updater;
use Drupal\Console\Style\DrupalStyle;

class SelfUpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription($this->trans('commands.self-update.description'))
            ->setHelp($this->trans('commands.self-update.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);
        $application = $this->getApplication();
        $pharName = 'drupal.phar';

        $updateStrategy = new GithubStrategy();
        $updateStrategy->setPackageName('drupal/console');
        $updateStrategy->setStability(GithubStrategy::STABLE);
        $updateStrategy->setPharName($pharName);
        $updateStrategy->setCurrentLocalVersion($application::VERSION);

        $updater = new Updater(null, false);
        $updater->setStrategyObject($updateStrategy);
        if ($updater->update()) {
            $output->success(
                sprintf(
                    $this->trans('commands.self-update.messages.success'),
                    $updater->getOldVersion(),
                    $pharName
                )
            );
        } else {
            $output->warning(
                sprintf(
                    $this->trans('commands.self-update.messages.current-version'),
                    $updater->getOldVersion()
                )
            );
        }

        $this->getApplication()->setDispatcher(null);
    }
}
