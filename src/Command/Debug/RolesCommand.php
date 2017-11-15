<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Roles\DebugCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class RolesCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class RolesCommand extends Command
{
    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * DebugCommand constructor.
     *
     * @param DrupalApi $drupalApi
     */
    public function __construct(
        DrupalApi $drupalApi
    ) {
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:roles')
            ->setDescription($this->trans('commands.debug.roles.description'))
            ->setAliases(['dusr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $roles = $this->drupalApi->getRoles();

        $tableHeader = [
            $this->trans('commands.debug.roles.messages.role-id'),
            $this->trans('commands.debug.roles.messages.role-name'),
        ];

        $tableRows = [];
        foreach ($roles as $roleId => $role) {
            $tableRows[] = [
                $roleId,
                $role
            ];
        }

        $io->table($tableHeader, $tableRows);
    }
}
