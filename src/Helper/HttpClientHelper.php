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

        return file_exists($destination);
    }

    public function getUrlAsString($url)
    {
        $response = $this->getClient()->get($url);

        if ($response->getStatusCode() == 200) {
            return (string) $response->getBody();
        }

        return null;
    }

    public function getUrlAsJson($url)
    {
        $response = $this->getClient()->get($url);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody());
        }

        return null;
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
