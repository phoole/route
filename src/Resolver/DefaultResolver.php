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

use Phoole\Base\Reference\ReferenceTrait;

/**
 * DefaultResolver
 *
 * Resolving [controllerName, methodName]into a request handler callable
 *
 * @package Phoole\Route
 */
class DefaultResolver implements ResolverInterface
{
    use ReferenceTrait;

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
     * used for resolving '${controller}' etc.
     *
     * @var array
     */
    protected $params;

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
     *
     * {@inheritDoc}
     */
    public function resolve($notCallable, array $params = []): callable
    {
        try {
            /**
             * dereference if any parameters used in callable definition
             * such as ['${controller}', '${action}']
             */
            $this->params = $params;
            $this->deReference($notCallable);

            return $this->appendSuffix($notCallable);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @param  array $notCallable
     * @return callable
     */
    protected function appendSuffix(array $notCallable): callable
    {
        $controller = $this->namespace . '\\' . $notCallable[0] . $this->controllerSuffix;
        $action = $notCallable[1] . $this->actionSuffix;
        return [new $controller(), $action];
    }

    /**
     * resolve parameters
     *
     * {@inheritDoc}
     */
    protected function getReference(string $name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        } else {
            return NULL;
        }
    }
}