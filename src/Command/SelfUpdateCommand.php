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
use Drupal\Console\Application;

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
        $updateStrategy = new GithubStrategy();
        $updateStrategy->setPackageName('drupal/console');
        $updateStrategy->setStability(GithubStrategy::STABLE);
        $updateStrategy->setPharName('console.phar');
        $updateStrategy->setCurrentLocalVersion(Application::VERSION);

        $updater = new Updater(null, false);
        $updater->setStrategyObject($updateStrategy);
        if ($updater->update()) {
            $output->writeln(
                sprintf(
                    $this->trans('commands.self-update.messages.success'),
                    $updater->getOldVersion(),
                    $updater->getNewVersion()
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    $this->trans('commands.self-update.messages.current-version'),
                    $updater->getOldVersion()
                )
            );
        }

        // Recommended by Commerce Guys CLI
        // https://github.com/platformsh/platformsh-cli/blob/7a122d3f3226d5e6ed0a0b74803158c51b31ad5e/src/Command/Self/SelfUpdateCommand.php#L72-L77
        // Errors appear if new classes are instantiated after this stage
        // (namely, Symfony's ConsoleTerminateEvent). This suggests PHP
        // can't read files properly from the overwritten Phar, or perhaps it's
        // because the autoloader's name has changed. We avoid the problem by
        // terminating now.
        exit;
    }
}
