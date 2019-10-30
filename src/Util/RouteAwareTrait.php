<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Route
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Route\Util;

use Phoole\Route\Util\Result;
use Phoole\Route\Parser\ParserInterface;

/**
 * RouteAwareTrait
 *
 * @package Phoole\Route
 */
trait RouteAwareTrait
{
    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var RouteGroup[]
     */
    protected $groups = [];

    /**
     * Add one route
     *
     * @param  Route $route
     * @return $this
     */
    public function addRoute(Route $route)
    {
        // group routes base on the URI prefix
        $prefix = $this->extractPrefix($route->getPattern());
        if (!isset($this->groups[$prefix])) {
            $this->groups[$prefix] = new RouteGroup($this->parser);
        }
        $this->groups[$prefix]->addRoute($route);
        return $this;
    }

    /**
     * Add a GET route
     *
     * @param  string $pattern
     * @param  mixed $handler
     * @param  array $defaults  default parameters if any
     * @return $this
     */
    public function addGet(string $pattern, $handler, array $defaults = [])
    {
        return $this->addRoute(new Route('GET', $pattern, $handler, $defaults));
    }

    /**
     * Add a POST route
     *
     * @param  string $pattern
     * @param  mixed $handler
     * @param  array $defaults  default parameters if any
     * @return $this
     */
    public function addPost(string $pattern, $handler, array $defaults = [])
    {
        return $this->addRoute(new Route('POST', $pattern, $handler, $defaults));
    }

    /**
     * Load routes from config array
     *
     * ```php
     * $routes = [
     *     ['GET', '/user/{uid}', function() {}, ['uid' => 12]],
     *     ...
     * ];
     * ```
     *
     * @param  array $routes
     * @return $this
     */
    protected function loadRoutes(array $routes)
    {
        foreach ($routes as $definition) {
            $method = $definition[0];
            $pattern = $definition[1];
            $handler = $definition[2];
            $defaults = $definition[3] ?? [];
            $this->addRoute(new Route($method, $pattern, $handler, $defaults));
        }
        return $this;
    }

    /**
     * @param  ParserInterface $parser
     * @return $this
     */
    protected function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;
        return $this;
    }

    /**
     * Extract the URI prefix, '/usr/' from '/user/uid/1'
     *
     * @param  string $uri  URI or pattern
     * @return string
     */
    protected function extractPrefix(string $uri): string
    {
        if (preg_match('~^(/[^/\[\]\{\}]+)~', $uri, $matched)) {
            return $matched[1];
        }
        return '/';
    }

    /**
     * Match a http request with all RouteGroup[s]
     *
     * @param  Result $result;
     * @return Result
     */
    protected function groupMatch(Result $result): Result
    {
        $uri = $result->getRequest()->getUri()->getPath();
        foreach ($this->groups as $prefix => $group) {
            // check prefix, then do matching
            if ($prefix === $this->extractPrefix($uri) && $group->match($result)) {
                return $result;
            }
        }
        return $result;
    }
}
