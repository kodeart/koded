<?php

namespace Tests\Koded\Framework;

use ReflectionClass;
use ReflectionProperty;

trait ObjectPropertyTrait
{
    private function objectProperty(object $object, string $property)
    {
        $prop = new ReflectionProperty($object, $property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    private function classProperty(object|string $objectOrClass, string $property)
    {
        $class = new ReflectionClass($objectOrClass);
        $prop = $class->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue();
    }
}
