<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cache\RebuildCommand.
 */

namespace Drupal\Console\Command\Cache;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Utils\Site;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RebuildCommand
 *
 * @package Drupal\Console\Command\Cache
 */
class RebuildCommand extends Command
{
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
            )->setAliases(['cr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cache = $input->getArgument('cache')?:'all';
        $this->site->loadLegacyFile('/core/includes/utility.inc');

        if ($cache && !$this->drupalApi->isValidCache($cache)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.cache.rebuild.messages.invalid-cache'),
                    $cache
                )
            );

            return 1;
        }

        $this->getIo()->newLine();
        $this->getIo()->comment($this->trans('commands.cache.rebuild.messages.rebuild'));

        if ($cache === 'all') {
            $this->drupalApi->drupal_rebuild(
                $this->classLoader,
                $this->requestStack->getCurrentRequest()
            );
        } else {
            $caches = $this->drupalApi->getCaches();
            $caches[$cache]->deleteAll();
        }

        $this->getIo()->success($this->trans('commands.cache.rebuild.messages.completed'));

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $cache = $input->getArgument('cache');
        if (!$cache) {
            $cacheKeys = array_keys($this->drupalApi->getCaches());

            $cache = $this->getIo()->choiceNoList(
                $this->trans('commands.cache.rebuild.questions.cache'),
                $cacheKeys,
                'all'
            );

            $input->setArgument('cache', $cache);
        }
    }
}
