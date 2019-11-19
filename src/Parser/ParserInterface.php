<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Route
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Route\Parser;

/**
 * ParserInterface
 *
 * @package Phoole\Route
 */
interface ParserInterface
{
    /**
     * Parse a named route pattern as follows into regular expression
     *
     * '/blog/{section}[/{year:\d+}[/{month:\d+}[/{date:\d+}]]]'
     *
     * @param  string $routeName
     * @param  string $routePattern
     * @return string the result regex for the pattern
     */
    public function parse(string $routeName, string $routePattern): string;

    /**
     * Match an URI path, return the matched route name and parameters
     *
     * @param  string $uriPath
     * @return array [ $routeName, $matchedParams ]
     */
    public function match(string $uriPath): array;
}