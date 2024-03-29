<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Route\Router;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;

class RouterTest extends TestCase
{
    private $obj;

    private $ref;

    public function getPrivateProperty($obj, $propertyName)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $property = $ref->getProperty($propertyName);
        $property->setAccessible(TRUE);
        return $property->getValue($obj);
    }

    /**
     * @covers Phoole\Route\Router::match()
     */
    public function testMatch()
    {
        $request = new ServerRequest('GET', 'http://bingo.com/usr/10');
        $this->obj->addGet(
            '/xxx[/{uid:d=20}][/{pid:d=1}]', function() {
            return FALSE;
        }
        );
        $pattern = '/usr[/{uid:d}][/{pid:d}]';
        $this->obj->addGet(
            $pattern, function() {
            return FALSE;
        }, ['pid' => 100]
        );
        $result = $this->obj->match($request);
        $this->assertTrue($result->isMatched());
        $route = $result->getRoute();
        $this->assertEquals($pattern, $route->getPattern());
        $params = Router::getParams($result->getRequest());
        $this->assertEquals(['pid' => 100, 'uid' => '10'], $params);
    }

    /**
     * Test auto controller/action match
     *
     * @covers Phoole\Route\Router::handleResult()
     */
    public function testHandleResult()
    {
        $request = new ServerRequest('GET', 'http://bingo.com/user/list');
        $this->obj->addGet('/{controller:xd}/{action:xd}', ['${controller}', '${action}']);
        $result = $this->obj->match($request);
        $this->assertTrue($result->isMatched());

        $this->expectExceptionMessage('userController');
        $handler = $this->invokeMethod('handleResult', [$result]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Router();
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = NULL;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }
}
