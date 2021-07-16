<?php

namespace Tests\Koded\Framework;

trait ObjectPropertyTrait
{
    private function property(object $object, string $property)
    {
        $prop = new \ReflectionProperty($object, $property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
