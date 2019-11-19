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
use Phoole\Route\Util\RouteAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Phoole\Route\Parser\ParserInterface;
use Phoole\Route\Parser\FastRouteParser;
use Phoole\Route\Resolver\DefaultResolver;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Phoole\Route\Resolver\ResolverInterface;

/**
 * Router
 *
 * @package Phoole\Route
 */
class Router implements MiddlewareInterface
{
    use RouteAwareTrait;
    /**
     * context name used for storing parsed parameters
     */
    const URI_PARAMETERS = '__parsedParams__';

    /**
     * controller/action name resolver
     *
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * Load route definitions and set the parser
     *
     * @param  array             $routes    route definitions
     * @param  ResolverInterface $resolver  controller/action name resolver
     * @param  ParserInterface   $parser    routes parser/matcher
     */
    public function __construct(
        array $routes = [],
        ?ResolverInterface $resolver = NULL,
        ?ParserInterface $parser = NULL
    ) {
        $this
            ->loadRoutes($routes)
            ->setResolver($resolver ?? new DefaultResolver())
            ->setParser($parser ?? new FastRouteParser());
    }

    /**
     * Middleware compliant
     *
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
     * Match request with predefined routes, returns a Result object
     *
     * @param  ServerRequestInterface $request
     * @return Result
     */
    public function match(ServerRequestInterface $request): Result
    {
        $result = new Result($request);
        return $this->routeMatch($result);
    }

    /**
     * Utility function for getting parameter values stored in the request
     *
     * @param  ServerRequestInterface $request
     * @return array
     */
    public static function getParams(ServerRequestInterface $request): array
    {
        $params = $request->getAttribute(Router::URI_PARAMETERS) ?? [];
        return $params;
    }

    /**
     * @param  ResolverInterface $resolver
     * @return $this
     */
    protected function setResolver(ResolverInterface $resolver)
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
            $handler = $this->resolver->resolve($handler, self::getParams($request));
            return $handler($request);
        }
    }
}