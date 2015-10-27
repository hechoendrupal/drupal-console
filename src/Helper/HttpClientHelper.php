<?php

/**
 * @file
 * Contains \Drupal\Console\Helper\HttpClientHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
use GuzzleHttp\Client;

/**
 * Class HttpClientHelper
 * @package \Drupal\Console\Helper\HttpClientHelper
 */
class HttpClientHelper extends Helper
{
    public function downloadFile($url, $destination)
    {
        $this->getClient()->get($url, array('sink' => $destination));
    }

    public function getHtml($url)
    {
        $response = $this->getClient()->get($url);

        return (string) $response->getBody();
    }

    public function getHeader($url, $header)
    {
        $response = $this->getClient()->get($url);
        $headerContent = $response->getHeader($header);
        if (!empty($headerContent) && is_array($headerContent)) {
            return array_shift($headerContent);
        }

        return $headerContent;
    }

    private function getClient()
    {
        return new Client();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'httpClient';
    }
}
