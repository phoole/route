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
 * DefaultResolver
 * Resolving [controllerName, methodName]into a request handler callable
 *
 * @package Phoole\Route
 */
class DefaultResolver implements ResolverInterface
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * set the namespace to search thru
     */
    public function __construct(string $namespace = '')
    {
        $this->namespace = $namespace;
    }

    /**
     * Resolve [controllerName, methodName] to a callable
     * {@inheritDoc}
     */
    public function resolve($notCallable): callable
    {
        try {
            $controllerName = $this->namespace . '\\' . $notCallable[0];
            $methodName = $notCallable[1];
            $result = [new $controllerName(), $methodName];
            if (is_callable($result)) {
                return $result;
            }
            throw new \Exception("unable to resolve " . $notCallable[0]);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }
}