<?php
/**
 * Copied temporary from https://github.com/padraic/phar-updater to bypass
 * alpha versions.
 */

/**
 * Humbug
 *
 * @category  Humbug
 * @package   Humbug
 * @copyright Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license   https://github.com/padraic/phar-updater/blob/master/LICENSE New BSD License
 *
 * This class is partially patterned after Composer's version parser.
 */

namespace Drupal\Console\Command\Self;

class VersionParser
{
    /**
     * @var array
     */
    private $versions;

    /**
     * @var string
     */
    private $modifier = '[._-]?(?:(stable|beta|b|RC|alpha|a|patch|pl|p)(?:[.-]?(\d+))?)?([.-]?dev)?';

    /**
     * @param array $versions
     */
    public function __construct(array $versions = array())
    {
        $this->versions = $versions;
    }

    /**
     * Get the most recent stable numbered version from versions passed to
     * constructor (if any)
     *
     * @return string
     */
    public function getMostRecentStable()
    {
        return $this->selectRecentStable();
    }

    /**
     * Get the most recent unstable numbered version from versions passed to
     * constructor (if any)
     *
     * @return string
     */
    public function getMostRecentUnStable()
    {
        return $this->selectRecentUnstable();
    }

    /**
     * Get the most recent stable or unstable numbered version from versions passed to
     * constructor (if any)
     *
     * @return string
     */
    public function getMostRecentAll()
    {
        return $this->selectRecentAll();
    }

    /**
     * Checks if given version string represents a stable numbered version
     *
     * @param  string $version
     * @return bool
     */
    public function isStable($version)
    {
        return $this->stable($version);
    }

    /**
     * Checks if given version string represents a 'pre-release' version, i.e.
     * it's unstable but not development level.
     *
     * @param  string $version
     * @return bool
     */
    public function isPreRelease($version)
    {
        return !$this->stable($version) && !$this->development($version);
    }

    /**
     * Checks if given version string represents an unstable or dev-level
     * numbered version
     *
     * @param  string $version
     * @return bool
     */
    public function isUnstable($version)
    {
        return !$this->stable($version);
    }

    /**
     * Checks if given version string represents a dev-level numbered version
     *
     * @param  string $version
     * @return bool
     */
    public function isDevelopment($version)
    {
        return $this->development($version);
    }

    private function selectRecentStable()
    {
        $candidates = array();
        foreach ($this->versions as $version) {
            if (!$this->stable($version)) {
                continue;
            }
            $candidates[] = $version;
        }
        if (empty($candidates)) {
            return false;
        }
        return $this->findMostRecent($candidates);
    }

    private function selectRecentUnstable()
    {
        $candidates = array();
        foreach ($this->versions as $version) {
            if ($this->stable($version) || $this->development($version)) {
                continue;
            }
            $candidates[] = $version;
        }
        if (empty($candidates)) {
            return false;
        }
        return $this->findMostRecent($candidates);
    }

    private function selectRecentAll()
    {
        $candidates = array();
        foreach ($this->versions as $version) {
            if ($this->development($version)) {
                continue;
            }
            $candidates[] = $version;
        }
        if (empty($candidates)) {
            return false;
        }
        return $this->findMostRecent($candidates);
    }

    private function findMostRecent(array $candidates)
    {
        $candidate = null;
        $tracker = null;
        foreach ($candidates as $version) {
            if (version_compare($candidate, $version, '<')) {
                $candidate = $version;
            }
        }
        return $candidate;
    }

    private function stable($version)
    {
        $version = preg_replace('{#.+$}i', '', $version);
        if ($this->development($version)) {
            return false;
        }
        preg_match('{'.$this->modifier.'$}i', strtolower($version), $match);
        if (!empty($match[3])) {
            return false;
        }
        if (!empty($match[1])) {
            if ('beta' === $match[1] || 'b' === $match[1]
                || 'alpha' === $match[1] || 'a' === $match[1]
                || 'rc' === $match[1]
            ) {
                // temporary fix to bypass alpha versions.
                return true;
            }
        }
        return true;
    }

    private function development($version)
    {
        if ('dev-' === substr($version, 0, 4) || '-dev' === substr($version, -4)) {
            return true;
        }
        if (1 == preg_match("/-\d+-[a-z0-9]{8,}$/", $version)) {
            return true;
        }
        return false;
    }
}
