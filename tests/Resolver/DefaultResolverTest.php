<?php

declare(strict_types=1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Route\Resolver\DefaultResolver;

class myController
{
    public function myAction()
    {
        return TRUE;
    }
}

class DefaultResolverTest extends TestCase
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
     * @covers Phoole\Route\Resolver\DefaultResolver::resolve()
     */
    public function testResolve()
    {
        $this->assertTrue(
            is_callable(
                $this->obj->resolve(['my', 'my'])
            )
        );
        $obj = new DefaultResolver(__NAMESPACE__, '', '');
        $this->assertTrue(
            is_callable(
                $obj->resolve(['myController', 'myAction'])
            )
        );
        $this->expectExceptionMessage('not found');
        $obj = new DefaultResolver();
        $this->assertTrue(
            is_callable(
                $obj->resolve(['Controller', 'myMethod'])
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new DefaultResolver(__NAMESPACE__);
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
