<?php

namespace Drupal\Console\Command\Taxonomy;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DeleteTermCommand.
 *
 * @package Drupal\eco_migrate
 */
class DeleteTermCommand extends Command
{
    use CommandTrait;

    /**
     * The entity_type storage.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * InfoCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        parent::__construct();
    }

    /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
            ->setName('taxonomy:term:delete')
            ->setDescription($this->trans('commands.taxonomy.term.delete.description'))
            ->addArgument('vid', InputArgument::REQUIRED);
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vid = $input->getArgument('vid');
        $io = new DrupalStyle($input, $output);

        $this->deleteExistingTerms($vid, $io);

        return 0;
    }

    /**
   * Destroy all existing terms before import
     *
   * @param $vid
   * @param $io
   */
    private function deleteExistingTerms($vid = null, DrupalStyle $io)
    {
        //Load the vid
        $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
        $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
            ->loadMultiple();

        if ($vid !== 'all') {
            $vid = [$vid];
        } else {
            $vid = array_keys($vocabularies);
        }

        foreach ($vid as $item) {
            if (!isset($vocabularies[$item])) {
                $io->error("Invalid vid: {$item}.");
            }
            $vocabulary = $vocabularies[$item];
            $terms = $termStorage->loadTree($vocabulary->id());

            foreach ($terms as $term) {
                $treal = $termStorage->load($term->tid);
                if ($treal !== null) {
                    $io->info("Deleting '{$term->name}' and all translations.");
                    $treal->delete();
                }
            }
        }
    }
}
