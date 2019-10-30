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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Result
 *
 * @package Phoole\Route
 */
class Result
{
    /**
     * @var Route the matched route
     */
    protected $route;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $handler;

    /**
     * @param  ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->setRequest($request);
    }

    /**
     * @param  Route $route
     * @return $this
     */
    public function setRoute(Route $route): Result
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * @param  ServerRequestInterface $request
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request): Result
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param  mixed $handler
     * @return Result
     */
    public function setHandler($handler): Result
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return bool
     */
    public function isMatched(): bool
    {
        if ($this->route) {
            return true;
        }
        return false;
    }
}
