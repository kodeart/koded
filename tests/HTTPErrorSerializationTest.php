<?php

namespace Tests\Koded\Framework;

use Koded\Http\HTTPError;
use Koded\Http\HTTPMethodNotAllowed;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use function serialize;
use function unserialize;

class HTTPErrorSerializationTest extends TestCase
{
    public function test_default_object_serialization()
    {
        $expected = new HTTPError(HttpStatus::METHOD_NOT_ALLOWED);
        $actual = unserialize(serialize($expected));

        $this->assertEquals($expected, $actual);
        $this->assertNotSame($expected, $actual, '(the instances are not same)');
    }

    public function test_full_object_serialization()
    {
        $expected = new HTTPMethodNotAllowed(['PUT'],
            instance: '/test',
            title: 'HTTPError Test',
            detail: 'A unit test for serializing the HTTPError object',
            type: '/url/for/more/details',
            headers: ['X-Test' => 'true']
        );
        $expected->setMember('foo', 'bar');
        $expected->setMember('bar', 'qux');

        $actual = unserialize(serialize($expected));
        $this->assertEquals($expected, $actual);
        $this->assertNotSame($expected, $actual);
    }
}
