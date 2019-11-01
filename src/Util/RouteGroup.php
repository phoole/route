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
 * RouteGroup
 *
 * @package Phoole\Route
 */
class RouteGroup
{
    /**
     * @var Route[]
     */
    protected $routes = [];

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * Set the parser
     *
     * @param  ParserInterface $parser
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Add a route to the route group
     *
     * @param  Route $route
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $pattern = $route->getPattern();
        $hash = md5($route->getPattern());
        if (isset($this->routes[$hash])) {
            // add new method to existing route
            $this->routes[$hash]->addMethods($route);
        } else {
            // add new route
            $this->routes[$hash] = $route;
        }

        $this->parser->parse($hash, $pattern);
        return $this;
    }

    /**
     * @param  Result
     * @return bool
     */
    public function match(Result $result): bool
    {
        $request = $result->getRequest();
        $uri = $request->getUri()->getPath();
        $matched = $this->parser->match($uri);

        if (empty($matched)) {
            // not matched
            return FALSE;
        } else {
            // matched
            return $this->fetchMatched($matched, $result);
        }
    }

    /**
     * @param  array  $matched
     * @param  Result $result
     * @return bool
     */
    protected function fetchMatched(array $matched, Result $result): bool
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

            return TRUE;
        }
        return FALSE;
    }
}