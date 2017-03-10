<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cache\RebuildCommand.
 */

namespace Drupal\Console\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class RebuildCommand
 *
 * @package Drupal\Console\Command\Cache
 */
class RebuildCommand extends Command
{
    use CommandTrait;

    /**
      * @var DrupalApi
      */
    protected $drupalApi;

    /**
     * @var Site
     */
    protected $site;

    protected $classLoader;
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * RebuildCommand constructor.
     *
     * @param DrupalApi    $drupalApi
     * @param Site         $site
     * @param $classLoader
     * @param RequestStack $requestStack
     */
    public function __construct(
        DrupalApi $drupalApi,
        Site $site,
        $classLoader,
        RequestStack $requestStack
    ) {
        $this->drupalApi = $drupalApi;
        $this->site = $site;
        $this->classLoader = $classLoader;
        $this->requestStack = $requestStack;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:rebuild')
            ->setDescription($this->trans('commands.cache.rebuild.description'))
            ->addArgument(
                'cache',
                InputArgument::OPTIONAL,
                $this->trans('commands.cache.rebuild.options.cache')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $cache = $input->getArgument('cache');
        $this->site->loadLegacyFile('/core/includes/utility.inc');

        if ($cache && !$this->drupalApi->isValidCache($cache)) {
            $io->error(
                sprintf(
                    $this->trans('commands.cache.rebuild.messages.invalid_cache'),
                    $cache
                )
            );

            return 1;
        }

        $io->newLine();
        $io->comment($this->trans('commands.cache.rebuild.messages.rebuild'));

        if ($cache === 'all') {
            $this->drupalApi->drupal_rebuild(
                $this->classLoader,
                $this->requestStack->getCurrentRequest()
            );
        } else {
            $caches = $this->drupalApi->getCaches();
            $caches[$cache]->deleteAll();
        }

        $io->success($this->trans('commands.cache.rebuild.messages.completed'));

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $cache = $input->getArgument('cache');
        if (!$cache) {
            $cacheKeys = array_keys($this->drupalApi->getCaches());

            $cache = $io->choiceNoList(
                $this->trans('commands.cache.rebuild.questions.cache'),
                $cacheKeys,
                'all'
            );

            $input->setArgument('cache', $cache);
        }
    }
}
