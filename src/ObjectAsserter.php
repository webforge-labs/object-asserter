<?php

declare(strict_types=1);

namespace Webforge\ObjectAsserter;

use Closure;
use Hamcrest\Matcher;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use InvalidArgumentException;
use stdClass;

/**
 *
 * Changelog:
 *   added equals8601Date()
 *   added $msg to equals8601Date() and fix expect/actual
 *   rewrote equalsDate without Carbon
 */
class ObjectAsserter
{
    /**
     * @var stdClass|array<int|string, mixed>|mixed
     */
    protected $object;

    protected self $context;

    /**
     * @var string[]
     */
    protected array $path;

    /**
     * @param array<int|string, mixed>|stdClass $object
     * @param self|null $context
     * @param string[] $path
     */
    public function __construct($object, self $context = null, array $path = [])
    {
        $this->object = $object;

        if (! $context) {
            $this->context = $this;
            $this->path = ['$root'];

            $this->assertThat(
                $this->msg('The given root object should be an object or an array'),
                $this->object,
                Matchers::anyOf(
                    Matchers::typeOf('object'),
                    Matchers::typeOf('array')
                )
            );
        } else {
            $this->path = $path;
            $this->context = $context;
        }
    }

    /**
     * Asserts that the object has a property with $name
     *
     * (regardless if it is empty or has some value)
     * @param string $name of the property
     * @param mixed $matcher use a Matchers:: assertion will default to equal when its
     */
    public function property($name, $matcher = null): self
    {
        $object = $this->assertedObject();
        $propertyPath = $this->addPath('.' . $name);

        $this->assertThat(
            $this->msg('property: %s does not exist', implode('', $propertyPath)),
            (new \ReflectionObject($object))->hasProperty($name),
            Matchers::equalTo(true)
        );

        $asserter = new self($object->$name, $this, $propertyPath);

        if (isset($matcher)) {
            $asserter->is($matcher);
        }

        return $asserter;
    }

    /**
     * @param Matcher|mixed $matcher use a Matchers:: matcher to check against the value of the property. If this is a string equalTo() is assumed
     */
    public function is($matcher): self
    {
        $matcher = $this->castMatcher($matcher);

        $this->assertThat($this->msg('%s does not match', $this->path()), $this->object, $matcher);
        return $this;
    }

    /**
     * @param Matcher|mixed $matcher
     */
    public function isNot($matcher): self
    {
        $matcher = $this->castMatcher($matcher);

        return $this->is(Matchers::not($matcher));
    }

    public function isNotEmptyString(): self
    {
        $this->assertThat(
            $this->msg('%s is not empty', $this->path()),
            $this->object,
            Matchers::nonEmptyString()
        );

        return $this;
    }

    public function contains(string $needle): self
    {
        $this->assertThat(
            $this->msg('%s does not contain substring %s', $this->path(), $needle),
            $this->object,
            Matchers::containsString($needle)
        );

        return $this;
    }

    /**
     * @param int|Matcher $matcher
     */
    public function length($matcher, string $message = ''): self
    {
        $matcher = $this->castMatcher($matcher);

        $this->assertThat(
            $this->msg('%s length does not match. %s', $this->path(), $message),
            $this->object,
            Matchers::arrayWithSize($matcher)
        );

        return $this;
    }

    /**
     * Asserts that the current array has an key $index
     *
     * @param Matcher|null|mixed $matcher use a matcher to check against the value of the property. equalTo is assumed
     * @param int|string $index
     */
    public function key($index, $matcher = null): self
    {
        $array = $this->assertedArray();
        $this->assertThat(
            $this->msg('%s does not have key %s', $this->path(), $index),
            $array,
            Matchers::hasKeyInArray(Matchers::identicalTo($index))
        );

        $keyPath = $this->addPath('[' . $index . ']');
        $asserter = new self($array[$index], $this, $keyPath);

        if ($matcher !== null) {
            $asserter->is($matcher);
        }

        return $asserter;
    }

    /**
     * Asserts that the current item is an array
     *
     * the array can be empty
     */
    public function isArray(): self
    {
        $this->assertThat(
            $this->msg('%s is not an array', $this->path()),
            $this->object,
            Matchers::typeOf('array')
        );

        return $this;
    }

    /**
     * Asserts that the current item is an object
     *
     * the object can be empty
     */
    public function isObject(): self
    {
        $this->assertThat($this->msg('%s is not an object', $this->path()), $this->object, Matchers::anObject());
        return $this;
    }

    private function assertedObject(): \stdClass
    {
        $this->isObject();
        /** @var stdClass $object */
        $object = $this->object;
        return $object;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function assertedArray(): array
    {
        $this->isArray();
        /** @var array<int|string, mixed> $array */
        $array = $this->object;

        return $array;
    }

    public function debug(): self
    {
        var_dump($this->object); // phpcs:ignore
        return $this;
    }

    /**
     * Taps into the current chain without changing the context
     *
     * $do = function($value, $objectAsserter)
     *
     * do is called with first parameter the actual data of context and with second argument an objectAsserter in the current context
     */
    public function tap(Closure $do): self
    {
        $do($this->get(), $this);
        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->object;
    }

    /**
     * @param string $item
     * @return string[]
     */
    protected function addPath(string $item): array
    {
        return array_merge($this->path, [$item]);
    }

    /**
     * Returns to the the last used property() or key() call
     */
    public function end(): self
    {
        return $this->context;
    }

    protected function path(): string
    {
        return implode('', $this->path);
    }

    /**
     * @param string $formatString
     * @param mixed ...$args
     */
    protected function msg(string $formatString, ...$args): string
    {
        return vsprintf($formatString, $args);
    }

    /**
     * @param mixed $matcher
     */
    protected function isMatcher($matcher): bool
    {
        return $matcher instanceof Matcher;
    }

    /**
     * @param string $messagePart
     * @param mixed $value
     * @param Matcher $matcher
     */
    private function assertThat(string $messagePart, $value, Matcher $matcher): void
    {
        $args = func_get_args();
        call_user_func_array([MatcherAssert::class, 'assertThat'], $args);
    }

    /**
     * @param Matcher|mixed $matcher
     */
    protected function castMatcher($matcher): Matcher
    {
        if (is_a($matcher, 'PHPUnit\Framework\Constraint\Constraint', false)) {
            throw new InvalidArgumentException('Hey there, sorry that you are still stuck with phpunit. Please use hamcrest matchers. Used: ' . get_class($matcher));
        }

        if (! $this->isMatcher($matcher)) {
            $matcher = Matchers::equalTo($matcher);
        }

        return $matcher;
    }

    /**
     * Checks the $matcher for all properties in the list
     *
     * it does not traverse to sub properties, it stays on the same object scope
     * @param Matcher|mixed $matcher
     * @param string[] $indexes
     */
    public function properties(array $indexes, $matcher): self
    {
        $matcher = $this->castMatcher($matcher);

        foreach ($indexes as $name) {
            $this->property($name, $matcher);
        }

        return $this;
    }

    public function equals8601Date(\DateTimeInterface $expected): self
    {
        $this->assertThat(
            $this->msg('%s should be an ISO 8601 date', $this->path()),
            $this->object,
            Matchers::isNonEmptyString()
        );

        assert(is_string($this->object));
        $date = \DateTime::createFromFormat('Y-m-d', $this->object);

        if ($date === false) {
            throw new \InvalidArgumentException(sprintf('Cannot handle input %s as format %s', $this->object, 'Y-m-d'));
        }

        $this->assertThat(
            $this->msg('%s should equal for ISO8601 date with. Parsed: "%s"', $this->path(), $this->object),
            $expected->format('d.m.Y'),
            Matchers::equalTo($date->format('d.m.Y')),
        );

        return $this;
    }
}
