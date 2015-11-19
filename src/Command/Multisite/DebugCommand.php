<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\DebugCommand.
 */

namespace Drupal\Console\Command\Multisite;

use Drupal\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SiteDebugCommand
 * @package Drupal\Console\Command\Site
 */
class DebugCommand extends Command
{
    /**
     * @{@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('multisite:debug')
            ->setDescription($this->trans('commands.multisite.debug.description'))
            ->setHelp($this->trans('commands.multisite.debug.help'));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getMessageHelper();
        $application = $this->getApplication();

        $sites = array();
        // Include Multi site settings
        include $this->getDrupalHelper()->getRoot() . '/sites/sites.php';


        if (empty($sites)) {
            $message->addErrorMessage(
                $this->trans('commands.multisite.debug.messages.no-multisites')
            );
            return;
        }


        $message->addInfoMessage(
            $this->trans('commands.multisite.debug.messages.site-format')
        );

        $table = new Table($output);

        $table->setHeaders(
            [
                $this->trans('commands.multisite.debug.messages.site'),
                $this->trans('commands.multisite.debug.messages.directory'),
            ]
        );

        foreach ($sites as $site => $directory) {
            $table->addRow(
                [
                  $site,
                    $this->getDrupalHelper()->getRoot()  . '/' . $directory
                ]
            );
        }
        $table->render();
    }
}
