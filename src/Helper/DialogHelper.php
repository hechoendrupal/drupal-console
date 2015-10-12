<?php

/**
 * @file
 * Contains Drupal\Console\Helper\DialogHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Console\Helper\DialogHelper as BaseDialogHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Helper\HelperTrait;

class DialogHelper extends BaseDialogHelper
{
    use HelperTrait;

    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(
            array(
            '',
            $this->getFormatterHelper()->formatBlock($text, $style, true),
            '',
            )
        );
    }

    public function getQuestion($question, $default, $sep = ':')
    {
        return $default ? sprintf(
            '<info>%s</info> [<comment>%s</comment>]%s ',
            $question,
            $default,
            $sep
        ) : sprintf('<info>%s</info>%s ', $question, $sep);
    }
}
