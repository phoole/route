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
     * @return RouteGroup $this
     */
    public function addRoute(Route $route): RouteGroup
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
        $mth = $request->getMethod();
        $res = $this->parser->match($uri);
        if (empty($res)) {
            return FALSE;
        }
        list($hash, $params) = $res;
        $route = $this->routes[$hash];
        if (isset($route->getMethods()[$mth])) {
            list($handler, $defaults) = $route->getMethods()[$mth];
            $request = $request->withAttribute(
                Router::URI_PARAMETERS,
                array_merge($defaults, $params)
            );
            $result->setHandler($handler);
            $result->setRoute($route);
            $result->setRequest($request);
            return TRUE;
        }
        return FALSE;
    }
}