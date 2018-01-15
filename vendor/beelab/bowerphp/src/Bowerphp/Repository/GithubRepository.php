<?php

namespace Bowerphp\Repository;

use Github\Client;
use Github\ResultPager;
use RuntimeException;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;
use vierbergenlars\SemVer\version;

/**
 * GithubRepository
 */
class GithubRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $tag = ['name' => null];

    /**
     * @var Client
     */
    protected $githubClient;

    /**
     * {@inheritdoc}
     *
     * @return GithubRepository
     */
    public function setUrl($url, $raw = true)
    {
        $url = preg_replace('/\.git$/', '', str_replace('git://', 'https://' . ($raw ? 'raw.' : ''), $url));
        $this->url = str_replace('raw.github.com', 'raw.githubusercontent.com', $url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param  Client           $githubClient
     * @return GithubRepository
     */
    public function setHttpClient(Client $githubClient)
    {
        $this->githubClient = $githubClient;
        // see https://developer.github.com/changes/2015-04-17-preview-repository-redirects/
        $this->githubClient->getHttpClient()->setHeaders(['Accept' => 'application/vnd.github.quicksilver-preview+json']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBower($version = 'master', $includeHomepage = false, $url = '')
    {
        if ('*' == $version) {
            $version = 'master';
        }
        if (!empty($url)) {
            // we need to save current $this->url
            $oldUrl = $this->url;
            // then, we call setUrl(), to get the http url
            $this->setUrl($url);
        }
        $json = $this->getDepBowerJson($version);
        if ($includeHomepage) {
            $array = json_decode($json, true);
            if (!empty($url)) {
                // here, we set again original $this->url, to pass it in bower.json
                $this->setUrl($oldUrl);
            }
            $array['homepage'] = $this->url;
            $json = json_encode($array, JSON_PRETTY_PRINT);
        }

        return $json;
    }

    /**
     * {@inheritdoc}
     */
    public function findPackage($rawCriteria = '*')
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        $paginator = new ResultPager($this->githubClient);
        $tags = $paginator->fetchAll($this->githubClient->api('repo'), 'tags', [$repoUser, $repoName]);

        // edge case: package has no tags
        if (0 === count($tags)) {
            $this->tag['name'] = 'master';

            return $this->tag['name'];
        }

        // edge case: user asked for latest package
        if ('latest' == $rawCriteria || '*' == $rawCriteria || empty($rawCriteria)) {
            $sortedTags = $this->sortTags($tags);
            $this->tag = end($sortedTags);

            return $this->tag['name'];
        }

        // edge case for versions with slash (like ckeditor). See also issue #120
        if (strpos($rawCriteria, '/') > 0) {
            $tagNames = array_column($tags, 'name');
            if (false !== $tag = array_search($rawCriteria, $tagNames, true)) {
                $this->tag = $tag;

                return $rawCriteria;
            }
        }

        try {
            $criteria = new expression($rawCriteria);
        } catch (SemVerException $sve) {
            throw new RuntimeException(sprintf('Criteria %s is not valid.', $rawCriteria), self::INVALID_CRITERIA, $sve);
        }
        $sortedTags = $this->sortTags($tags);

        // Yes, the php-semver lib does offer a maxSatisfying() method similar the code below.
        // We're not using it because it will throw an exception on what it considers to be an
        // "invalid" candidate version, and not continue checking the rest of the candidates.
        // So, even if it's faster than this code, it's not a complete solution.
        $matches = array_filter($sortedTags, function ($tag) use ($criteria) {
            $candidate = $tag['parsed_version'];

            return $criteria->satisfiedBy($candidate) ? $tag : false;
        });

        // If the array has elements, the LAST element is the best (highest numbered) version.
        if (count($matches) > 0) {
            // @todo Get rid of this side effect?
            $this->tag = array_pop($matches);

            return $this->tag['name'];
        }

        throw new RuntimeException(sprintf('%s: No suitable version for %s was found.', $repoName, $rawCriteria), self::VERSION_NOT_FOUND);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelease($type = 'zip')
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));

        return $this->githubClient->api('repo')->contents()->archive($repoUser, $repoName, $type . 'ball', $this->tag['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        $paginator = new ResultPager($this->githubClient);
        $tags = $paginator->fetchAll($this->githubClient->api('repo'), 'tags', [$repoUser, $repoName]);
        // edge case: no tags
        if (0 === count($tags)) {
            return [];
        }

        $sortedTags = $this->sortTags($tags);  // Filters out bad tag specs

        return array_keys($sortedTags);
    }

    /**
     * Get remote bower.json file (or package.json file)
     *
     * @param  string $version
     * @return string
     */
    private function getDepBowerJson($version)
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        $contents = $this->githubClient->api('repo')->contents();
        if ($contents->exists($repoUser, $repoName, 'bower.json', $version)) {
            $json = $contents->download($repoUser, $repoName, 'bower.json', $version);
        } else {
            $isPackageJson = true;
            if ($contents->exists($repoUser, $repoName, 'package.json', $version)) {
                $json = $contents->download($repoUser, $repoName, 'package.json', $version);
            } elseif ('master' != $version) {
                return $this->getDepBowerJson('master');
            }
            // try anyway. E.g. exists() return false for Modernizr, but then it downloads :-|
            $json = $contents->download($repoUser, $repoName, 'package.json', $version);
        }

        if ("\xef\xbb\xbf" == substr($json, 0, 3)) {
            $json = substr($json, 3);
        }

        // for package.json, remove dependencies (see the case of Modernizr)
        if (isset($isPackageJson)) {
            $array = json_decode($json, true);
            if (isset($array['dependencies'])) {
                unset($array['dependencies']);
            }
            $json = json_encode($array, JSON_PRETTY_PRINT);
        }

        return $json;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function clearGitURL($url)
    {
        $partsToClean = [
            'git://',
            'git@github.com:',
            'https://',
            'github.com/',
            'raw.githubusercontent.com/',
        ];
        foreach ($partsToClean as $part) {
            $url = str_replace($part, '', $url);
        }

        if ('.git' == substr($url, -4)) {
            $url = substr($url, 0, -4);
        }

        return $url;
    }

    /**
     * Why do we have to do this? Your guess is as good as mine.
     * The only flaw I've seen in the semver lib we're using,
     * and the regex's in there are too complicated to mess with.
     *
     * @param string $rawValue
     *
     * @return string
     */
    private function fixupRawTag($rawValue)
    {
        if (0 === strpos($rawValue, 'v')) {
            $rawValue = substr($rawValue, 1);
        }
        // WHY NOT SCRUB OUT PLUS SIGNS, RIGHT?
        $foundIt = strpos($rawValue, '+');
        if (false !== $foundIt) {
            $rawValue = substr($rawValue, 0, $foundIt);
        }
        $rawValue = strtr($rawValue, ['.alpha' => '-alpha', '.beta' => '-beta', '.dev' => '-dev']);
        $pieces = explode('.', $rawValue);
        $count = count($pieces);
        if (0 == $count) {
            $pieces[] = '0';
            $count = 1;
        }
        for ($add = $count; $add < 3; ++$add) {
            $pieces[] = '0';
        }
        $return = implode('.', array_slice($pieces, 0, 3));

        return $return;
    }

    /**
     * @param array $tags
     * @param bool  $excludeUnstables
     *
     * @return array
     */
    private function sortTags(array $tags, $excludeUnstables = true)
    {
        $return = [];

        // Don't include invalid tags
        foreach ($tags as $tag) {
            try {
                $fixedName = $this->fixupRawTag($tag['name']);
                $v = new version($fixedName);
                if ($v->valid()) {
                    $version = $v->getVersion();
                    if ($excludeUnstables && $this->isNotStable($v)) {
                        continue;
                    }
                    $tag['parsed_version'] = $v;
                    $return[$version] = $tag;
                }
            } catch (\Exception $ex) {
                // Skip
            }
        }

        uasort($return, function ($a, $b) {
            return version::compare($a['parsed_version'], $b['parsed_version']);
        });

        return $return;
    }

    /**
     * @param version $version
     *
     * @return bool
     */
    private function isNotStable(version $version)
    {
        return count($version->getPrerelease()) > 0;
    }
}
