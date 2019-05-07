<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\DatabaseTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class DatabaseTrait
 *
 * @package Drupal\Console\Command\Shared
 */
trait DatabaseTrait
{
    /**
     * @return mixed
     */
    public function dbHostQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.migrate.execute.questions.db-host'),
            '127.0.0.1'
        );
    }

    /**
     * @return mixed
     */
    public function dbNameQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.migrate.execute.questions.db-name')
        );
    }

    /**
     * @return mixed
     */
    public function dbUserQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.migrate.execute.questions.db-user')
        );
    }

    /**
     * @return mixed
     */
    public function dbPassQuestion()
    {
        return $this->getIo()->askHiddenEmpty(
            $this->trans('commands.migrate.execute.questions.db-pass')
        );
    }

    /**
     * @return mixed
     */
    public function dbPrefixQuestion()
    {
        return $this->getIo()->askEmpty(
            $this->trans('commands.migrate.execute.questions.db-prefix')
        );
    }

    /**
     * @return mixed
     */
    public function dbPortQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.migrate.execute.questions.db-port'),
            '3306'
        );
    }
}
