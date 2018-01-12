<?php

/**
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter.Text
 */

namespace Twitter\Text;

use Twitter\Text\Regex;
use Twitter\Text\Extractor;
use Twitter\Text\StringUtils;

/**
 * Twitter Validator Class
 *
 * Performs "validation" on tweets.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter.Text
 */
class Validator
{

    /**
     * The maximum length of a tweet.
     *
     * @var int
     * @deprecated will be removed
     */
    const MAX_LENGTH = 140;

    /**
     * The length of a short URL beginning with http:
     *
     * @var int
     * @deprecated will be removed
     */
    protected $short_url_length = 23;

    /**
     * The length of a short URL beginning with http:
     *
     * @var int
     * @deprecated will be removed
     */
    protected $short_url_length_https = 23;

    /**
     *
     * @var Extractor
     */
    protected $extractor = null;

    /**
     * Provides fluent method chaining.
     *
     * @param mixed   $config Setup short URL length from Twitter API /help/configuration response.
     *
     * @see __construct()
     *
     * @return Validator
     */
    public static function create($config = null)
    {
        return new self($config);
    }

    /**
     * Reads in a tweet to be parsed and validates it.
     *
     * @param mixed   $config Setup short URL length from Twitter API /help/configuration response.
     */
    public function __construct($config = null)
    {
        if (!empty($config)) {
            $this->setConfiguration($config);
        }
        $this->extractor = Extractor::create();
    }

    /**
     * Setup short URL length from Twitter API /help/configuration response
     *
     * @param mixed $config
     * @return Validator
     * @link https://dev.twitter.com/docs/api/1/get/help/configuration
     * @deprecated will be removed
     */
    public function setConfiguration($config)
    {
        if (is_array($config)) {
            // setup from array
            if (isset($config['short_url_length'])) {
                $this->setShortUrlLength($config['short_url_length']);
            }
            if (isset($config['short_url_length_https'])) {
                $this->setShortUrlLengthHttps($config['short_url_length_https']);
            }
        } elseif (is_object($config)) {
            // setup from object
            if (isset($config->short_url_length)) {
                $this->setShortUrlLength($config->short_url_length);
            }
            if (isset($config->short_url_length_https)) {
                $this->setShortUrlLengthHttps($config->short_url_length_https);
            }
        }

        return $this;
    }

    /**
     * Set the length of a short URL beginning with http:
     *
     * @param mixed $length
     * @return Validator
     * @deprecated will be removed
     */
    public function setShortUrlLength($length)
    {
        $this->short_url_length = intval($length);
        return $this;
    }

    /**
     * Get the length of a short URL beginning with http:
     *
     * @return int
     * @deprecated will be removed
     */
    public function getShortUrlLength()
    {
        return $this->short_url_length;
    }

    /**
     * Set the length of a short URL beginning with https:
     *
     * @param mixed $length
     * @return Validator
     * @deprecated will be removed
     */
    public function setShortUrlLengthHttps($length)
    {
        $this->short_url_length_https = intval($length);
        return $this;
    }

    /**
     * Get the length of a short URL beginning with https:
     *
     * @return int
     * @deprecated will be removed
     */
    public function getShortUrlLengthHttps()
    {
        return $this->short_url_length_https;
    }

    /**
     * Check whether a tweet is valid.
     *
     * @param string        $tweet  The tweet to validate.
     * @param Configuration $config using configration
     * @return boolean  Whether the tweet is valid.
     * @deprecated instead use \Twitter\Text\Parser::parseText()
     */
    public function isValidTweetText($tweet, Configuration $config = null)
    {
        if (is_null($config)) {
            // default use v1 config
            $config = Configuration::v1();
        }

        $result = Parser::create($config)->parseTweet($tweet);

        return $result->valid;
    }

    /**
     * Check whether a username is valid.
     *
     * @param string $username The username to validate.
     * @return boolean  Whether the username is valid.
     */
    public function isValidUsername($username)
    {
        $length = StringUtils::strlen($username);
        if (empty($username) || !$length) {
            return false;
        }
        $extracted = $this->extractor->extractMentionedScreennames($username);
        return count($extracted) === 1 && $extracted[0] === substr($username, 1);
    }

    /**
     * Check whether a list is valid.
     *
     * @param string $list The list name to validate.
     * @return boolean  Whether the list is valid.
     */
    public function isValidList($list)
    {
        $length = StringUtils::strlen($list);
        if (empty($list) || !$length) {
            return false;
        }
        preg_match(Regex::getValidMentionsOrListsMatcher(), $list, $matches);
        $matches = array_pad($matches, 5, '');
        return isset($matches) && $matches[1] === '' && $matches[4] && !empty($matches[4]) && $matches[5] === '';
    }

    /**
     * Check whether a hashtag is valid.
     *
     * @param string $hashtag The hashtag to validate.
     * @return boolean  Whether the hashtag is valid.
     */
    public function isValidHashtag($hashtag)
    {
        $length = StringUtils::strlen($hashtag);
        if (empty($hashtag) || !$length) {
            return false;
        }
        $extracted = $this->extractor->extractHashtags($hashtag);
        return count($extracted) === 1 && $extracted[0] === substr($hashtag, 1);
    }

    /**
     * Check whether a URL is valid.
     *
     * @param string   $url               The url to validate.
     * @param boolean  $unicode_domains   Consider the domain to be unicode.
     * @param boolean  $require_protocol  Require a protocol for valid domain?
     *
     * @return boolean  Whether the URL is valid.
     */
    public function isValidURL($url, $unicode_domains = true, $require_protocol = true)
    {
        $length = StringUtils::strlen($url);
        if (empty($url) || !$length) {
            return false;
        }

        preg_match(Regex::getValidateUrlUnencodedMatcher(), $url, $matches);
        $match = array_shift($matches);
        if (!$matches || $match !== $url) {
            return false;
        }

        list($scheme, $authority, $path, $query, $fragment) = array_pad($matches, 5, '');

        # Check scheme, path, query, fragment:
        if (($require_protocol && !(
                self::isValidMatch($scheme, Regex::getValidateUrlSchemeMatcher())
                && preg_match('/^https?$/i', $scheme)
            ))
            || !self::isValidMatch($path, Regex::getValidateUrlPathMatcher())
            || !self::isValidMatch($query, Regex::getValidateUrlQueryMatcher(), true)
            || !self::isValidMatch($fragment, Regex::getValidateUrlFragmentMatcher(), true)) {
            return false;
        }

        # Check authority:
        $authorityPattern = $unicode_domains ? Regex::getValidateUrlUnicodeAuthorityMatcher() : Regex::getValidateUrlAuthorityMatcher();

        return self::isValidMatch($authority, $authorityPattern);
    }

    /**
     * Determines the length of a tweet.  Takes shortening of URLs into account.
     *
     * @param string $tweet The tweet to validate.
     * @param Configuration $config using configration
     * @return int  the length of a tweet.
     * @deprecated instead use \Twitter\Text\Parser::parseText()
     */
    public function getTweetLength($tweet, Configuration $config = null)
    {
        if (is_null($config)) {
            // default use v1 config
            $config = Configuration::v1();
        }

        $result = Parser::create($config)->parseTweet($tweet);

        return $result->weightedLength;
    }

    /**
     * A helper function to check for a valid match.  Used in URL validation.
     *
     * @param string   $string    The subject string to test.
     * @param string   $pattern   The pattern to match against.
     * @param boolean  $optional  Whether a match is compulsory or not.
     *
     * @return boolean  Whether an exact match was found.
     */
    protected static function isValidMatch($string, $pattern, $optional = false)
    {
        $found = preg_match($pattern, $string, $matches);
        if (!$optional) {
            return (($string || $string === '') && $found && $matches[0] === $string);
        } else {
            return !(($string || $string === '') && (!$found || $matches[0] !== $string));
        }
    }
}
