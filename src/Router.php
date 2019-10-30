<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Route
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Route;

use Phoole\Route\Util\Result;
use Phoole\Route\Util\RouteGroup;
use Phoole\Route\Util\RouteAwareTrait;
use Phoole\Route\Parser\ParserInterface;
use Phoole\Route\Parser\FastRouteParser;
use Phoole\Route\Resolver\DefaultResolver;
use Phoole\Route\Resolver\ResolverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Router
 *
 * @package Phoole\Route
 */
class Router implements MiddlewareInterface
{
    use RouteAwareTrait;

    const URI_PARAMETERS = '_parsedParams';
    
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * Load route definitions and set the parser
     *
     * @param  array $routes route definitions
     * @param  ResolverInterface $resolver
     * @param  ParserInterface $parser
     *
     */
    public function __construct(
        array $routes = [],
        ResolverInterface $resolver = null,
        ParserInterface $parser = null
    ) {
        $this
            ->loadRoutes($routes)
            ->setResolver($resolver ?? new DefaultResolver())
            ->setParser($parser ?? new FastRouteParser());
    }

    /**
     * Match http request with predefined routes, returns a Result object
     *
     * @param  ServerRequestInterface $request
     * @return Result
     */
    public function match(ServerRequestInterface $request): Result
    {
        $result = new Result($request);
        return $this->groupMatch($result);
    }

    /**
     * {@inheritDoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $result = $this->match($request);
        if ($result->isMatched()) {
            return $this->handleResult($result);
        } else {
            return $handler->handle($request);
        }
    }

    /**
     * @param  ResolverInterface $resolver
     * @return Router $this
     */
    protected function setResolver(ResolverInterface $resolver): Router
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @param  Result $result
     * @return ResponseInterface
     * @throws \InvalidArgumentException    if resolver failure
     */
    protected function handleResult(Result $result): ResponseInterface
    {
        $request = $result->getRequest();
        $handler = $result->getHandler();

        if (is_callable($handler)) {
            return $handler($request);
        } elseif ($handler instanceof RequestHandlerInterface) {
            return $handler->handle($request);
        } else {
            $handler = $this->resolver->resolve($handler);
            return $handler($request);
        }
    }
}
