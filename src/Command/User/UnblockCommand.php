<?php
/**
 * @file
 * Contains \Drupal\Console\Command\User\UnblockCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class UnblockCommand extends UserBase
{
    /**
     * UnblockCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        parent::__construct($entityTypeManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:unblock')
            ->setDescription($this->trans('commands.user.unblock.description'))
            ->setHelp($this->trans('commands.user.unblock.help'))
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                $this->trans('commands.user.unblock.options.user')
            )
            ->setAliases(['uu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->getUserArgument();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getArgument('user');

        $userEntity = $this->getUserEntity($user);

        if (!$userEntity) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.user.unblock.errors.invalid-user'),
                    $user
                )
            );

            return 1;
        }

        if (!$userEntity->isBlocked()) {
          $this->getIo()->warning(
            sprintf(
              $this->trans('commands.user.unblock.warnings.unblocked-user'),
              $user
            )
          );

          return 1;
        }

        try {
            $userEntity->activate();

            $userEntity->save();

            $this->getIo()->success(
              sprintf(
                $this->trans('commands.user.unblock.messages.unblock-successful'),
                $user
              )
            );

            return 0;
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }
    }
}
