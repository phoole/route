<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Route
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types = 1);

namespace Phoole\Route\Util;

use Phoole\Route\Router;
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
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Add a GET route
     *
     * @param  string $pattern
     * @param  mixed  $handler
     * @param  array  $defaults  default parameters if any
     * @return $this
     */
    public function addGet(string $pattern, $handler, array $defaults = [])
    {
        return $this->addRoute(new Route('GET', $pattern, $handler, $defaults));
    }

    /**
     * Add one route
     *
     * @param  Route $route
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $pattern = $route->getPattern();
        $hash = md5($route->getPattern());
        if (isset($this->routes[$hash])) {
            $this->routes[$hash]->addMethods($route);
        } else {
            $this->routes[$hash] = $route;
        }

        $this->parser->parse($hash, $pattern);
        return $this;
    }

    /**
     * Add a POST route
     *
     * @param  string $pattern
     * @param  mixed  $handler
     * @param  array  $defaults  default parameters if any
     * @return $this
     */
    public function addPost(string $pattern, $handler, array $defaults = [])
    {
        return $this->addRoute(new Route('POST', $pattern, $handler, $defaults));
    }

    /**
     * Load routes from config array
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
     * Match a route with all predefined routes
     *
     * @param  Result $result  ;
     * @return Result
     */
    protected function routeMatch(Result $result): Result
    {
        $uri = $result->getRequest()->getUri()->getPath();

        $matched = $this->parser->match($uri);
        if (!empty($matched)) {
            $this->fetchMatched($matched, $result);
        }

        return $result;
    }

    /**
     * @param  array  $matched
     * @param  Result $result
     * @return void
     */
    protected function fetchMatched(array $matched, Result $result): void
    {
        // fetch the matched route
        list($hash, $params) = $matched;
        $route = $this->routes[$hash];

        // check if method exists
        $request = $result->getRequest();
        $method = $request->getMethod();
        if (isset($route->getMethods()[$method])) {
            // update request
            list($handler, $defaults) = $route->getMethods()[$method];
            $request = $request->withAttribute(
                Router::URI_PARAMETERS,
                array_merge($defaults, $params)
            );

            // update result
            $result->setHandler($handler);
            $result->setRoute($route);
            $result->setRequest($request);
        }
    }
}