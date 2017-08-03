<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\DatabaseTrait.
 */

namespace Drupal\Console\Command\Shared;

use Symfony\Component\Console\Input\InputInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DatabaseTrait
 *
 * @package Drupal\Console\Command\Shared
 */
trait DatabaseTrait
{
    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbHostQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-host'),
            '127.0.0.1'
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbNameQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-name')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbUserQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-user')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbPassQuestion(DrupalStyle $io)
    {
        return $io->askHiddenEmpty(
            $this->trans('commands.migrate.execute.questions.db-pass')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbPrefixQuestion(DrupalStyle $io)
    {
        return $io->askEmpty(
            $this->trans('commands.migrate.execute.questions.db-prefix')
        );
    }

    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function dbPortQuestion(DrupalStyle $io)
    {
        return $io->ask(
            $this->trans('commands.migrate.execute.questions.db-port'),
            '3306'
        );
    }
}
