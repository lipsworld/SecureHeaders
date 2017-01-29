<?php

namespace Aidantwoods\SecureHeaders\Operations;

use Aidantwoods\SecureHeaders\Header;
use Aidantwoods\SecureHeaders\HeaderBag;
use Aidantwoods\SecureHeaders\Operation;

class ModifyCookies implements Operation
{
    private $blacklist;
    private $field;

    private $matchSubstring = false;

    public function __construct(array $blacklist, $field)
    {
        $this->blacklist = $blacklist;
        $this->field = $field;
    }

    public static function matchingFully(array $blacklist, $field)
    {
        return new static($blacklist, $field);
    }

    public static function matchingPartially(array $blacklist, $field)
    {
        $instance = new static($blacklist, $field);
        $instance->matchSubstring = true;

        return $instance;
    }

    /**
     * Transform the given set of headers
     *
     * @param HeaderBag $headers
     * @return void
     */
    public function modify(HeaderBag $headers)
    {
        foreach ($this->extractCookies($headers) as $cookieHeader) {
            $cookieName = $cookieHeader->getFirstAttributeName();

            if ($this->matches($cookieName)) {
                $cookieHeader->enableAttribute($this->field);
            }
        }
    }

    /**
     * @param HeaderBag $headers
     * @return Header[]
     */
    private function extractCookies(HeaderBag $headers)
    {
        return array_filter(
            $headers->get(),
            function (Header $header) {
                return $header->is('set-cookie');
            }
        );
    }

    private function matches($cookieName)
    {
        if ($this->matchSubstring) {
            return $this->matchesSubstring($cookieName);
        } else {
            return $this->matchesFully($cookieName);
        }
    }

    private function matchesSubstring($cookieName)
    {
        foreach ($this->blacklist as $forbidden) {
            if (strpos(strtolower($cookieName), $forbidden) !== false) {
                return true;
            }
        }
    }

    private function matchesFully($cookieName)
    {
        return in_array(
            strtolower($cookieName),
            $this->blacklist,
            true
        );
    }
}
