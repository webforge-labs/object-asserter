<?php

declare(strict_types=1);

namespace Webforge\ObjectAsserter;

trait AssertionsTrait // is a PHPUnit\Framework\TestCase
{
    /**
     * @param mixed $object
     * @return ObjectAsserter
     */
    protected static function assertThatObject($object): \Webforge\ObjectAsserter\ObjectAsserter
    {
        self::assertInstanceOf(\stdClass::class, $object, '$root should be an object');
        return new ObjectAsserter($object);
    }

    /**
     * @param mixed $array
     * @return ObjectAsserter
     */
    protected static function assertThatArray($array): \Webforge\ObjectAsserter\ObjectAsserter
    {
        self::assertIsArray($array, '$root should be an array');
        return new ObjectAsserter($array);
    }
}
