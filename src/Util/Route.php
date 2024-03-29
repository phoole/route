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

/**
 * Route
 *
 * @package Phoole\Route
 */
class Route
{
    /**
     * @var string[]
     */
    protected $methods;

    /**
     * @var string
     */
    protected $pattern;

    /**
     * @param  string|string[] $method    HTTP method[s]
     * @param  string          $pattern   URI pattern to match
     * @param  mixed           $handler   request handler
     * @param  array           $defaults  default parameters
     * @throws \LogicException            if pattern not right
     */
    public function __construct(
        $method,
        string $pattern,
        $handler,
        array $defaults = []
    ) {
        $this
            ->setPattern($pattern, $defaults)
            ->setMethods($method, $handler, $defaults);
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set route pattern
     *
     * @param  string  $pattern
     * @param  array  &$defaults
     * @return $this
     * @throws \LogicException  if pattern not right
     */
    public function setPattern(string $pattern, &$defaults)
    {
        if ($this->validatePattern($pattern)) {
            list($pattern, $params) = $this->extractDefaults($pattern);
            $this->pattern = $pattern;
            if (!empty($params)) {
                $defaults = array_merge($params, $defaults);
            }
        }
        return $this;
    }

    /**
     * Add new methods from another route
     *
     * @param  Route $route  another route
     * @return $this
     */
    public function addMethods(Route $route)
    {
        $this->methods = array_merge($this->methods, $route->getMethods());
        return $this;
    }

    /**
     * @return  array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Set methods(with related handler/defaults)
     *
     * @param  string|string[] $method
     * @param  mixed           $handler
     * @param  array           $defaults
     * @return $this
     */
    public function setMethods($method, $handler, array $defaults)
    {
        $methods = is_string($method) ?
            preg_split('~[^A-Z]+~', strtoupper($method), -1, PREG_SPLIT_NO_EMPTY) :
            array_map('strtoupper', $method);
        foreach ($methods as $mth) {
            $this->methods[$mth] = [$handler, $defaults];
        }
        return $this;
    }

    /**
     * Validate the pattern
     *
     * @param  string $pattern
     * @return bool
     * @throws \LogicException  if not valid pattern
     */
    protected function validatePattern(string $pattern): bool
    {
        if (
            substr_count($pattern, '[') !== substr_count($pattern, ']') ||
            substr_count($pattern, '{') !== substr_count($pattern, '}')
        ) {
            throw new \LogicException("Invalid route pattern '$pattern'");
        }
        return TRUE;
    }

    /**
     * Extract default values from the pattern
     *
     * @param  string $pattern
     * @return array
     */
    protected function extractDefaults(string $pattern): array
    {
        $vals = [];
        $regex = '~\{([a-zA-Z][a-zA-Z0-9_]*+)[^\}]*(=[a-zA-Z0-9._]++)\}~';
        if (preg_match_all($regex, $pattern, $matches, \PREG_SET_ORDER)) {
            $srch = $repl = [];
            foreach ($matches as $m) {
                $srch[] = $m[0];
                $repl[] = str_replace($m[2], '', $m[0]);
                $vals[$m[1]] = substr($m[2], 1);
            }
            $pattern = str_replace($srch, $repl, $pattern);
        }
        return [$pattern, $vals];
    }
}