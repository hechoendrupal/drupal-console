<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ComposerCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\ComposerGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerCommand extends Command
{
    use ArrayInputTrait;
    use ConfirmationTrait;
    use ModuleTrait;

    /**
     * @var ComposerGenerator
     */
    protected $generator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;


    /**
     * ComposerCommand constructor.
     *
     * @param ComposerGenerator $generator
     * @param Manager $extensionManager
     * @param Validator $validator
     */
    public function __construct(
      ComposerGenerator $generator,
      Manager $extensionManager,
      Validator $validator
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
          ->setName('generate:composer')
          ->setDescription($this->trans('commands.generate.composer.description'))
          ->setHelp($this->trans('commands.generate.composer.composer'))
          ->addOption(
            'module',
            null,
            InputOption::VALUE_REQUIRED,
            $this->trans('commands.common.options.module')
          )
          ->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            $this->trans('commands.generate.composer.options.name')
          )
          ->addOption(
            'type',
            null,
            InputOption::VALUE_REQUIRED,
            $this->trans('commands.generate.composer.options.type')
          )
          ->addOption(
            'description',
            null,
            InputOption::VALUE_REQUIRED,
            $this->trans('commands.generate.composer.options.description')
          )
          ->addOption(
            'keywords',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.generate.composer.options.keywords')
          )
          ->addOption(
            'license',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.composer.options.license')
          )
          ->addOption(
            'homepage',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.composer.options.homepage')
          )
          ->addOption(
            'minimum-stability',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.composer.options.minimum-stability')
          )
          ->addOption(
            'authors',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.generate.composer.options.authors')
          )
          ->addOption(
            'support',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.generate.composer.options.support')
          )
          ->addOption(
            'required',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.generate.composer.options.required')
          )->setAliases(['gcom']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $module = $this->getModuleOption();

        // --name option
        $name = $input->getOption('name');
        if (!$name) {
            $name = $this->getIo()->askEmpty(
              $this->trans('commands.generate.composer.questions.name'),
              'drupal/' . $module
            );
            $input->setOption('name', $name);
        }

        // --type option
        $type = $input->getOption('type');
        if (!$type) {
            $type = $this->getIo()->askEmpty(
              $this->trans('commands.generate.composer.questions.type'),
              'drupal-module'
            );
            $input->setOption('type', $type);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->askEmpty(
              $this->trans('commands.generate.composer.questions.description')
            );
            $input->setOption('description', $description);
        }

        // --keywords option
        $keywords = $input->getOption('keywords');
        if (!$keywords) {
            if ($this->getIo()->confirm(
              $this->trans('commands.generate.composer.questions.add-keywords'),
              false
            )) {
                $keywords = [];
                while (true) {
                    $keyword = $this->getIo()->askEmpty(
                      $this->trans('commands.generate.composer.questions.keyword')
                    );

                    if (empty($keyword) || is_numeric($keyword)) {
                        break;
                    }

                    $keywords[] = $keyword;

                }
                $input->setOption('keywords', $keywords);
            }
        }

        // --license option
        $license = $input->getOption('license');
        if (!$license) {
            $license = $this->getIo()->askEmpty(
              $this->trans('commands.generate.composer.questions.license'),
              'GPL-2.0+'
            );
            $input->setOption('license', $license);
        }

        // --homepage option
        $homepage = $input->getOption('homepage');
        if (!$homepage) {
            $homepage = $this->getIo()->askEmpty(
              $this->trans('commands.generate.composer.questions.homepage'),
              'https://www.drupal.org/project/' . $module
            );
            $input->setOption('homepage', $homepage);
        }

        // --minimum-stability option
        $minimumStability = $input->getOption('minimum-stability');
        if (!$minimumStability) {
            $stabilityOptions = [
              'stable',
              'dev',
              'alpha',
              'beta',
              'RC',
            ];
            $minimumStability = $this->getIo()->choiceNoList(
              $this->trans('commands.generate.composer.questions.minimum-stability'),
              $stabilityOptions,
              '',
              true
            );
            $input->setOption('minimum-stability', $minimumStability);
        }

        // --authors option
        $authors = $input->getOption('authors');
        if (!$authors) {
            if ($this->getIo()->confirm(
              $this->trans('commands.generate.composer.questions.add-author'),
              false
            )) {
                $authorItems = [];
                while (true) {
                    $authorName = $this->getIo()->askEmpty(
                      $this->trans('commands.generate.composer.questions.author-name')
                    );
                    $authorEmail = $this->getIo()->askEmpty(
                      $this->trans('commands.generate.composer.questions.author-email')
                    );
                    $authorHomepage = $this->getIo()->askEmpty(
                      $this->trans('commands.generate.composer.questions.author-homepage')
                    );
                    $authorRole = $this->getIo()->askEmpty(
                      $this->trans('commands.generate.composer.questions.author-role')
                    );

                    if (!empty($authorName) || !empty($authorEmail) || !empty($authorHomepage) || !empty($authorRole)) {
                        $authorItems[] = [
                          'name' => $authorName,
                          'email' => $authorEmail,
                          'homepage' => $authorHomepage,
                          'role' => $authorRole,
                        ];
                    }

                    $this->getIo()->newLine();

                    if (!$this->getIo()->confirm(
                      $this->trans('commands.generate.composer.questions.add-another-author'),
                      false
                    )) {
                        break;
                    }

                }
                $this->getIo()->newLine(2);
                $input->setOption('authors', $authorItems);
            }
        }

        // --support option
        $support = $input->getOption('support');
        if (!$support) {
            if ($this->getIo()->confirm(
              $this->trans('commands.generate.composer.questions.add-support'),
              false
            )) {
                $supportChannels = [
                  'email',
                  'issues',
                  'forum',
                  'wiki',
                  'irc',
                  'source',
                  'docs',
                  'rss',
                ];
                $supportItems = [];
                while (true) {
                    $supportChannel = $this->getIo()->choiceNoList(
                      $this->trans('commands.generate.composer.questions.support-channel'),
                      $supportChannels,
                      '',
                      true
                    );

                    $supportUrl = $this->getIo()->ask(
                      $this->trans('commands.generate.composer.questions.support-value')
                    );

                    $supportItems[] = [
                      'channel' => $supportChannel,
                      'url' => $supportUrl,
                    ];

                    $this->getIo()->newLine();

                    if (!$this->getIo()->confirm(
                      $this->trans('commands.generate.composer.questions.add-another-support'),
                      false
                    )) {
                        break;
                    }

                }
                $this->getIo()->newLine(2);
                $input->setOption('support', $supportItems);
            }
        }

        // --required option
        $required = $input->getOption('required');
        if (!$required) {
            if ($this->getIo()->confirm(
              $this->trans('commands.generate.composer.questions.add-required'),
              false
            )) {
                $requiredItems = [];
                while (true) {

                    $requiredName = $this->getIo()->ask(
                      $this->trans('commands.generate.composer.questions.required-name')
                    );

                    $requiredVersion = $this->getIo()->ask(
                      $this->trans('commands.generate.composer.questions.required-version')
                    );

                    $requiredItems[] = [
                      'name' => $requiredName,
                      'version' => $requiredVersion,
                    ];

                    $this->getIo()->newLine();

                    if (!$this->getIo()->confirm(
                      $this->trans('commands.generate.composer.questions.add-another-required'),
                      false
                    )) {
                        break;
                    }

                }
                $input->setOption('required', $requiredItems);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $input->getOption('module');
        $name = $input->getOption('name');
        $type = $input->getOption('type');
        $description = $input->getOption('description');
        $keywords = $input->getOption('keywords');
        $license = $input->getOption('license');
        $homepage = $input->getOption('homepage');
        $minimumStability = $input->getOption('minimum-stability');
        $authors = $input->getOption('authors');
        $support = $input->getOption('support');
        $required = $input->getOption('required');
        $noInteraction = $input->getOption('no-interaction');

        // Parse nested data.
        if ($noInteraction) {
            $authors = $this->explodeInlineArray($authors);
            $support = $this->explodeInlineArray($support);
            $required = $this->explodeInlineArray($required);
        }

        $this->generator->generate([
          'machine_name' => $module,
          'name' => $name,
          'type' => $type,
          'description' => $description,
          'keywords' => $keywords,
          'license' => $license,
          'homepage' => $homepage,
          'minimum_stability' => $minimumStability,
          'authors' => $authors,
          'support_items' => $support,
          'required_items' => $required,
        ]);

        return 0;
    }


}
