<?php

declare(strict_types=1);

namespace FuzzyWuzzy;

/**
 * Convenience methods for working with string values.
 *
 * @author Michael Crumm <mike@crumm.net>
 */
class StringProcessor
{
    /**
     * @param string $str
     * @return string
     */
    public static function nonAlnumToWhitespace(string $str): string
    {
        return preg_replace('/(?i)\W/u', ' ', $str);
    }

    /**
     * @param string $str
     * @return string
     */
    public static function upcase(string $str): string
    {
        return strtoupper($str);
    }

    /**
     * @param string $str
     * @return string
     */
    public static function downcase(string $str): string
    {
        return strtolower($str);
    }

    /**
     * @param string[] $pieces
     * @param string $glue
     * @return string
     */
    public static function join(array $pieces, string $glue = ' '): string
    {
        return Collection::coerce($pieces)->join($glue);
    }

    /**
     * @param string $str
     * @param string $delimiter
     * @return Collection
     */
    public static function split(string $str, string $delimiter = ' '): Collection
    {
        return new Collection(explode($delimiter, $str));
    }

    /**
     * @param string $str
     * @param string $chars
     * @return string
     */
    public static function strip(string $str, string $chars = " \t\n\r\0\x0B"): string
    {
        return trim($str, $chars);
    }
}
