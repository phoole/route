<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Route
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Route\Parser;

/**
 * FastRouteParser
 *
 * @package Phoole\Route
 */
class FastRouteParser implements ParserInterface
{
    /**
     * flag for new route added.
     *
     * @var    bool
     */
    protected $modified = false;

    /**
     * regex storage
     *
     * @var    string[]
     */
    protected $regex = [];

    /**
     * group position map
     *
     * @var    array
     */
    protected $maps = [];

    /**
     * chunk size 4 - 12 for merging regex
     *
     * @var    int
     */
    protected $chunk = 8;

    /**
     * combined regex (cache)
     *
     * @var    string[]
     */
    protected $data = [];

    /**
     * another cache
     *
     * @var    string[][]
     */
    protected $xmap = [];

    /**
     * @var    string
     */
    const MATCH_GROUP_NAME = "\s*([a-zA-Z][a-zA-Z0-9_]*)\s*";
    const MATCH_GROUP_TYPE = ":\s*([^{}]*(?:\{(?-1)\}[^{}]*)*)";
    const MATCH_SEGMENT = "[^/]++";
    
    /**
     * pattern shortcuts
     *
     * @var    string[]
     */
    protected $shortcuts = [
        ':d}'   => ':[0-9]++}',             // digit only
        ':l}'   => ':[a-z]++}',             // lower case
        ':u}'   => ':[A-Z]++}',             // upper case
        ':a}'   => ':[0-9a-zA-Z]++}',       // alphanumeric
        ':c}'   => ':[0-9a-zA-Z+_\-\.]++}', // common chars
        ':nd}'  => ':[^0-9/]++}',           // not digits
        ':xd}'  => ':[^0-9/][^/]*+}',       // no leading digits
    ];

    /**
     * {@inheritDoc}
     */
    public function parse(string $routeName, string $routePattern): string
    {
        list($regex, $map) = $this->convert($routePattern);
        $this->maps[$routeName] = $map;
        $this->doneProcess($routeName, $regex);
        return $regex;
    }

    /**
     * {@inheritDoc}
     */
    public function match(string $uriPath): array
    {
        $matches = [];
        foreach ($this->getRegexData() as $i => $regex) {
            if (preg_match($regex, $uriPath, $matches)) {
                $map = array_flip($this->xmap[$i]);
                $key = $map[count($matches) - 1];
                return $this->fixMatches($key, $matches);
            }
        }
        return $matches;
    }

    /**
     * Convert to regex
     *
     * @param  string $pattern pattern to parse
     * @return array
     */
    protected function convert(string $pattern): array
    {
        $ph = sprintf("\{%s(?:%s)?\}", self::MATCH_GROUP_NAME, self::MATCH_GROUP_TYPE);

        // count placeholders
        $map = $m = [];
        if (preg_match_all('~' . $ph . '~x', $pattern, $m)) {
            $map = $m[1];
        }

        $result = preg_replace(
            [
            '~' . $ph . '(*SKIP)(*FAIL) | \[~x', '~' . $ph . '(*SKIP)(*FAIL) | \]~x',
            '~\{' . self::MATCH_GROUP_NAME . '\}~x', '~' . $ph . '~x',
            ],
            ['(?:', ')?', '{\\1:' . self::MATCH_SEGMENT . '}', '(\\2)'],
            strtr('/' . trim($pattern, '/'), $this->shortcuts)
        );

        return [$result, $map];
    }

    /**
     * Merge several (chunk size) regex into one
     *
     * @return array
     */
    protected function getRegexData(): array
    {
        // load from cache
        if (!$this->modified) {
            return $this->data;
        }

        // merge
        $this->data = array_chunk($this->regex, $this->chunk, true);
        foreach ($this->data as $i => $arr) {
            $map = $this->getMapData($arr, $this->maps);
            $str = '~^(?|';
            foreach ($arr as $k => $reg) {
                $str .= $reg . str_repeat('()', $map[$k] - count($this->maps[$k])) . '|';
            }
            $this->data[$i] = substr($str, 0, -1) . ')$~x';
            $this->xmap[$i] = $map;
        }
        $this->modified = false;
        return $this->data;
    }

    /**
     * @param  array $arr
     * @param  array $maps
     * @return array
     */
    protected function getMapData(array $arr, array $maps): array
    {
        $new1 = [];
        $keys = array_keys($arr);
        foreach ($keys as $k) {
            $new1[$k] = count($maps[$k]) + 1; // # of PH for route $k
        }
        $new2 = array_flip($new1);
        $new3 = array_flip($new2);

        foreach ($keys as $k) {
            if (!isset($new3[$k])) {
                foreach (range(1, 200) as $i) {
                    $cnt = $new1[$k] + $i;
                    if (!isset($new2[$cnt])) {
                        $new2[$cnt] = $k;
                        $new3[$k] = $cnt;
                        break;
                    }
                }
            }
        }
        return $new3;
    }

    /**
     * Fix matched placeholders, return with unique route key
     *
     * @param  string $name the route key/name
     * @param  array $matches desc
     * @return array [ $name, $matches ]
     */
    protected function fixMatches(string $name, array $matches): array
    {
        $res = [];
        $map = $this->maps[$name];
        foreach ($matches as $idx => $val) {
            if ($idx > 0 && '' !== $val) {
                $res[$map[$idx - 1]] = $val;
            }
        }
        return [$name, $res];
    }

    /**
     * Update regex pool etc.
     *
     * @param  string $routeName
     * @param  string $regex
     */
    protected function doneProcess(
        string $routeName,
        string $regex
    ) {
        $this->regex[$routeName] = $regex;
        $this->modified = true;
    }
}
