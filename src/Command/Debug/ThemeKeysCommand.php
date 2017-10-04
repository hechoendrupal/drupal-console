<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\DebugKeysCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Core\Theme\Registry;
use Drupal\Console\Core\Style\DrupalStyle;

class ThemeKeysCommand extends Command
{
  use CommandTrait;

  /**
   * @var Registry
   */
  protected $themeRegistry;

  /**
   * DebugCommand constructor.
   *
   * @param \Drupal\Core\Theme\Registry $themeRegistry
   *   The theme registry service.
   */
  public function __construct(Registry $themeRegistry) {
    $this->themeRegistry = $themeRegistry;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('debug:theme:keys')
      ->setDescription($this->trans('commands.debug.theme.keys.description'))
      ->setHelp($this->trans('commands.debug.theme.keys.help'))
      ->addArgument(
        'key',
        InputArgument::OPTIONAL,
        $this->trans('commands.debug.theme.keys.arguments.key')
      )
      ->setAliases(['dtk']);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new DrupalStyle($input, $output);

    $key = $input->getArgument('key');

    if (!$key) {
      $this->themeKeysList($io);
    }
    else {
      $this->themeKeysDetail($io, $key);
    }
  }

  protected function themeKeysList(DrupalStyle $io)
  {
    $tableHeader = [
      $this->trans('commands.debug.theme.keys.table-headers.key'),
      $this->trans('commands.debug.theme.keys.table-headers.provider-type'),
      $this->trans('commands.debug.theme.keys.table-headers.provider'),
    ];

    $tableRows = [];
    $keys = $this->themeRegistry->get();
    foreach ($keys as $key => $definition) {
      $tableRows[] = [
        $key,
        $this->trans('commands.debug.theme.keys.provider-types.' . strtr($definition['type'], '_', '-')),
        basename($definition['theme path']),
      ];
    }
    array_multisort($tableRows, array_column($tableRows, 0));

    $io->table($tableHeader, $tableRows);
  }

  protected function themeKeysDetail(DrupalStyle $io, $key)
  {
    $tableHeader = [
      $this->trans('commands.debug.theme.keys.table-headers.key'),
      $this->trans('commands.debug.theme.keys.table-headers.value')
    ];

    $keys = $this->themeRegistry->get();
    $definition = $keys[$key];

    $tableRows = [];
    foreach ($definition as $key => $value) {
      if (is_object($value) && method_exists($value, '__toString')) {
        $value = (string) $value;
      } elseif (is_array($value) || is_object($value)) {
        $value = Yaml::dump($value);
      } elseif (is_bool($value)) {
        $value = ($value) ? 'TRUE' : 'FALSE';
      }
      $tableRows[$key] = [$key, $value];
    }
    ksort($tableRows);
    $io->table($tableHeader, array_values($tableRows));
  }
}
