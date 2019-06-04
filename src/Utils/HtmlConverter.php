<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Html2Text.
 */

namespace Drupal\Console\Utils;

use Html2Text\Html2Text;

class HtmlConverter
{
    /**
     * Converts an HTML string to a best fit plain text string.
     *
     * @param string $html
     *   Source HTML
     * @param array $options
     *   Set configuration options
     *
     * @return string|null
     *   The text, converted from HTML.
     */
    public static function html2text($html, $options = []) {
        $out = (new Html2Text($html, $options))->getText();
        return str_replace("\t", "    ", $out);
    }
}
