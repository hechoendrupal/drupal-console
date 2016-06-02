<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Self\ManifestStrategy.
 */

namespace Drupal\Console\Command\Self;

use Humbug\SelfUpdate\Exception\HttpRequestException;
use Humbug\SelfUpdate\Exception\JsonParsingException;
use Humbug\SelfUpdate\Strategy\StrategyInterface;
use Humbug\SelfUpdate\Updater;

class ManifestStrategy implements StrategyInterface
{
    /**
 * @var string 
*/
    private $manifestUrl;

    /**
 * @var array 
*/
    private $manifest;

    /**
 * @var array 
*/
    private $availableVersions;

    /**
 * @var string 
*/
    private $localVersion;

    /**
 * @var bool 
*/
    private $allowMajor = false;

    /**
   * @param string $localVersion
   * @param bool   $allowMajor
   * @param string $manifestUrl
   */
    public function __construct($localVersion, $allowMajor = false, $manifestUrl)
    {
        $this->localVersion = $localVersion;
        $this->manifestUrl = $manifestUrl;
        $this->allowMajor = $allowMajor;
    }

    /**
   * Download the remote Phar file.
   *
   * @param Updater $updater
   *
   * @throws \Exception on failure
   */
    public function download(Updater $updater)
    {
        $version = $this->getCurrentRemoteVersion($updater);
        $versionInfo = $this->getAvailableVersions();
        if (!isset($versionInfo[$version]['url'])) {
            throw new \Exception(
                sprintf('Failed to find download URL for version %s', $version)
            );
        }
        if (!isset($versionInfo[$version]['sha1'])) {
            throw new \Exception(
                sprintf(
                    'Failed to find download checksum for version %s',
                    $version
                )
            );
        }

        $downloadResult = file_get_contents($versionInfo[$version]['url']);
        if ($downloadResult === false) {
            throw new HttpRequestException(
                sprintf(
                    'Request to URL failed: %s',
                    $versionInfo[$version]['url']
                )
            );
        }

        $saveResult = file_put_contents(
            $updater->getTempPharFile(),
            $downloadResult
        );
        if ($saveResult === false) {
            throw new \Exception(
                sprintf('Failed to write file: %s', $updater->getTempPharFile())
            );
        }

        $tmpSha = sha1_file($updater->getTempPharFile());
        if ($tmpSha !== $versionInfo[$version]['sha1']) {
            unlink($updater->getTempPharFile());
            throw new \Exception(
                sprintf(
                    'The downloaded file does not have the expected SHA-1 hash: %s',
                    $versionInfo[$version]['sha1']
                )
            );
        }
    }

    /**
   * Get available versions to update to.
   *
   * @return array
   *   An array keyed by the version name, whose elements are arrays
   *   containing version information ('name', 'sha1', and 'url').
   */
    private function getAvailableVersions()
    {
        if (!isset($this->availableVersions)) {
            $this->availableVersions = [];
            list($localMajorVersion, ) = explode('.', $this->localVersion, 2);
            foreach ($this->getManifest() as $item) {
                $version = $item['version'];
                if (!$this->allowMajor) {
                    list($majorVersion, ) = explode('.', $version, 2);
                    if ($majorVersion !== $localMajorVersion) {
                        continue;
                    }
                }
                $this->availableVersions[$version] = $item;
            }
        }

        return $this->availableVersions;
    }

    /**
   * Download the manifest.
   *
   * @return array
   */
    private function getManifest()
    {
        if (!isset($this->manifest)) {
            $manifestContents = file_get_contents($this->manifestUrl);
            if ($manifestContents === false) {
                throw new \RuntimeException(sprintf('Failed to download manifest: %s', $this->manifestUrl));
            }

            $this->manifest = json_decode($manifestContents, true);
            if (null === $this->manifest || json_last_error() !== JSON_ERROR_NONE) {
                throw new JsonParsingException(
                    'Error parsing package manifest'
                    . (function_exists('json_last_error_msg') ? ': ' . json_last_error_msg() : '')
                );
            }
        }

        return $this->manifest;
    }

    /**
   * Retrieve the current version available remotely.
   *
   * @param Updater $updater
   *
   * @return string|bool
   */
    public function getCurrentRemoteVersion(Updater $updater)
    {
        $versionParser = new VersionParser(array_keys($this->getAvailableVersions()));

        return $versionParser->getMostRecentStable();
    }

    /**
   * Retrieve the current version of the local phar file.
   *
   * @param Updater $updater
   *
   * @return string
   */
    public function getCurrentLocalVersion(Updater $updater)
    {
        return $this->localVersion;
    }
}
