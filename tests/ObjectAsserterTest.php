<?php

namespace Webforge\ObjectAsserter\Tests;

use DateTime;
use Hamcrest\AssertionError;
use Hamcrest\Matchers;
use stdClass;
use Webforge\ObjectAsserter\ObjectAsserter;
use PHPUnit\Framework\TestCase;

class ObjectAsserterTest extends TestCase
{
    private ObjectAsserter $composer;
    private ObjectAsserter $array1;
    private object $composerScalar;

    protected function setUp(): void
    {
        $this->composer = new ObjectAsserter($this->composerScalar = (object) [
            "name" => "webforge/object-asserter",
            "description" => "Fluent DSL to do assertions on scalar structures",
            "type" => "library",
            "require" => [
                "hamcrest/hamcrest-php" => "^2.0"
            ],
            "require-dev" => [
                "phpunit/phpunit" => "^9.5",
                "phpstan/phpstan" => "^0.12.92",
                "ergebnis/phpstan-rules" => "^0.15.3"
            ],
            "license" => "MIT",
            "autoload" => (object) [
                "psr-4" => [
                    "Webforge\ObjectAsserter\\" => "src/"
                ]
            ],
            "autoload-dev" => (object)[
                "psr-4" => [
                    "Webforge\ObjectAsserter\\" => "tests/"
                ]
            ],
            "authors" => [
                (object)[
                    "name" => "Philipp Scheit",
                    "email" => "p.scheit@ps-webforge.com"
                ]
            ],
            "minimum-stability" => "stable"
        ]);

        $this->array1 = new ObjectAsserter([
            'list1'=>'value1',
            'list2'=>(object) [
                'date'=>'2020-01-01'
            ],
            'list3'=>'',
            'list4'=>null
        ]);
    }

    public function testIsObject(): void
    {
        $this->composer->isObject();

        $this->expectAssertionFailure();
        $this->array1->isObject();
    }

    public function testIsArray(): void
    {
        $this->array1->isArray();

        $this->expectAssertionFailure();
        $this->composer->isArray();
    }

    public function testProperty(): void
    {
        $this->composer->property('name');
        $this->composer->property('require-dev')->isArray();

        $this->expectAssertionFailure();
        $this->composer->property('not-here');
    }

    public function testPropertyDoesNotWorkWithArrayKeys(): void
    {
        $this->expectAssertionFailure();
        $this->array1->property('list1');
    }

    public function testLength(): void
    {
        $this->array1->isArray()->length(4);

        $this->expectAssertionFailure();
        $this->array1->length(1);
    }

    public function testKey(): void
    {
        $this->array1->key('list1');
        $this->array1->key('list2');

        $this->expectAssertionFailure();
        $this->array1->key('not-existing');
    }

    public function testKeyCanTestForMatchers(): void
    {
        $this->array1->key('list1', 'value1');
        $this->array1->key('list1', Matchers::identicalTo('value1'));

        $this->expectAssertionFailure();
        $this->array1->key('list1', 'not-equal-value');
    }

    public function testKeyisNotComparing0WithStrings(): void
    {
        $this->expectAssertionFailure('Expected: array with key <0>');
        $this->composer->property('require')->key(0);
    }

    public function testGet(): void
    {
        $author = $this->composer->property('authors')
            ->key(0)->get();

        self::assertSame(
            $this->composerScalar->authors[0],
            $author,
            'get returns the current object in the scalar as reference'
        );
    }

    public function testIsNotEmptyString(): void
    {
        $this->composer->property('license')->isNotEmptyString();

        $this->expectAssertionFailure();
        $this->array1->property('list3')->isNotEmptyString();
    }

    public function testNullIsNotAnEmptyString(): void
    {
        $this->expectAssertionFailure('Expected: a non-empty string');
        $this->array1->key('list4')->isNotEmptyString();
    }

    public function testProperties(): void
    {
        $scope = $this->composer->properties(['name', 'description', 'type'], Matchers::nonEmptyString())->get();

        self::assertSame($scope, $this->composerScalar);
    }

    public function testContains(): void
    {
        $this->composer->property('name')->contains('webforge');

        $this->expectAssertionFailure();
        $this->composer->property('name')->contains('this-not');
    }


    public function testTap(): void
    {
        $this->composer->property('authors')
            ->tap(function(array $authors) {
                self::assertArrayHasKey(0, $authors);
            });
    }

    public function testIsNot(): void
    {
        $this->composer->property('license')->isNot(Matchers::typeOf('bool'));

        $this->expectAssertionFailure('Expected: not a string');
        $this->composer->property('license')->isNot(Matchers::typeOf('string'));
    }

    public function testIs(): void
    {
        $this->composer->property('require')->is(Matchers::typeOf('array'))
            ->key('hamcrest/hamcrest-php')->is(Matchers::containsString('2.'));

        $this->expectAssertionFailure();
        $this->composer->property('require')->is(Matchers::anInstanceOf(\stdClass::class));
    }

    public function testEndShiftsTheScope(): void
    {
        $author = $this->composer->property('authors')
            ->key(0)
                ->property('name', 'Philipp Scheit')->end()
                ->get();

        self::assertSame($author, $this->composerScalar->authors[0]);
    }

    public function testEquals8601Date(): void
    {
        $this->array1->key('list2')
            ->property('date')
                ->equals8601Date(new DateTime('01.01.2020'));

        $this->expectAssertionFailure();
        $this->array1->key('list2')
            ->property('date')
            ->equals8601Date(new DateTime('02.01.2020'));
    }

    private function expectAssertionFailure(string $message = null): void
    {
        $this->expectException(AssertionError::class);
        if ($message !== null) {
            $this->expectExceptionMessageMatches('|'.preg_quote($message, '|').'|');
        }
    }
}
