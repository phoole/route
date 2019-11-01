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
     * @var string
     */
    protected $controllerSuffix;

    /**
     * var string
     */
    protected $actionSuffix;

    /**
     * set the namespace to search thru
     *
     * @param  string $namespace
     * @param  string $controllerSuffix
     * @param  string $actionSuffix
     */
    public function __construct(
        string $namespace = '',
        string $controllerSuffix = 'Controller',
        string $actionSuffix = 'Action'
    ) {
        $this->namespace = $namespace;
        $this->controllerSuffix = $controllerSuffix;
        $this->actionSuffix = $actionSuffix;
    }

    /**
     * Resolve [controller, action] to a callable
     * {@inheritDoc}
     */
    public function resolve($notCallable): callable
    {
        try {
            $controller = $this->namespace . '\\' . $notCallable[0] . $this->controllerSuffix;
            $action = $notCallable[1] . $this->actionSuffix;
            $result = [new $controller(), $action];
            if (is_callable($result)) {
                return $result;
            }
            throw new \Exception("unable to resolve " . $notCallable[0]);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }
}