<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Route
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types = 1);

namespace Phoole\Route\Resolver;

/**
 * ResolverInterface
 * Resolving into a request handler callable
 *
 * @package Phoole\Route
 */
interface ResolverInterface
{
    /**
     * Resolve the given into a callable of
     *
     * ```php
     * callable(ServerRequestInterface $request): ResponseInterface
     * ```
     *
     * @param  mixed $notCallable  e.g. [controllerName, methodName]
     * @param  array $params       parameters used to resolve callable
     * @return callable
     * @throws \InvalidArgumentException  unable to resolve
     */
    public function resolve($notCallable, array $params = []): callable;
}