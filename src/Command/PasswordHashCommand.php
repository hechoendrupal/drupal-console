<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\PasswordCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class PasswordHashCommand extends ContainerAwareCommand
{
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('password:hash')
          ->setDescription($this->trans('commands.password.hash.description'))
          ->setHelp($this->trans('commands.password.hash.help'))
          ->addArgument('password', InputArgument::IS_ARRAY, $this->trans('commands.password.hash.options.password'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $passwords = $input->getArgument('password');

        $password_hasher = $this->getPassHandler();

        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(
          [
            $this->trans('commands.password.hash.messages.password'),
            $this->trans('commands.password.hash.messages.hash'),
          ]);

        $table->setlayout($table::LAYOUT_COMPACT);

        foreach ($passwords as $password) {
            $table->addRow([
            $password,
            $password_hasher->hash($password)
          ]);
        }

        $table->render($output);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        $passwords = $input->getArgument('password');
        if (!$passwords) {
            $passwords = array();
            while (true) {
                $password = $dialog->askAndValidate(
            $output,
            $dialog->getQuestion(count($passwords)>0?$this->trans('commands.password.hash.questions.other-password'):$this->trans('commands.password.hash.questions.password'), ''),
            function ($pass) use ($passwords) {
              if (!empty($pass) || count($passwords) >= 1) {
                  return $pass;
              } else {
                  throw new \InvalidArgumentException(
                  sprintf($this->trans('commands.password.hash.questions.invalid-pass'), $pass)
                );
              }
            },
            false,
            '',
            null
          );

                if (empty($password)) {
                    break;
                }

                $passwords[] = $password;
            }
        }

        $input->setArgument('password', $passwords);
    }
}
