<?php

declare(strict_types=1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Route\Router;
use Phoole\Route\Util\Result;
use Phoole\Route\Util\RouteGroup;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class RouterTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Router();
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = null;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $parameters);
    }

    public function getPrivateProperty($obj, $propertyName)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @covers Phoole\Route\Router::match()
     */
    public function testMatch()
    {
        $request = new ServerRequest('GET', 'http://bingo.com/usr/10');

        $this->obj->addGet('/xxx[/{uid:d=20}][/{pid:d=1}]', function() {
            return false;
        });

        $pattern = '/usr[/{uid:d}][/{pid:d}]';
        $this->obj->addGet($pattern, function() {
            return false;
        }, ['pid' => 100]);

        $result = $this->obj->match($request);
        $this->assertTrue($result->isMatched());

        $route = $result->getRoute();
        $this->assertEquals($pattern, $route->getPattern());

        $params = $result->getRequest()->getAttribute(Router::URI_PARAMETERS);
        $this->assertEquals(['pid' => 100, 'uid' => '10'], $params);
    }
}