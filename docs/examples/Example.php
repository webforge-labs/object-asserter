<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Webforge\ObjectAsserter\ObjectAsserter;

class Example extends TestCase
{
    public function testThatMyComposerJsonIsCorrect_withPhpunit(): void
    {
        $composerObject = $this->getComposerJsonDecoded();

        self::assertIsObject($composerObject);

        self::assertObjectHasAttribute('name', $composerObject);
        self::assertIsString($composerObject->name);
        self::assertStringContainsString('object-asserter', $composerObject->name);

        self::assertObjectHasAttribute('require', $composerObject);
        self::assertIsArray($composerObject->require);
        self::assertCount(1, $composerObject->require);
        self::assertArrayHasKey('amcrest/hamcrest-php', $composerObject->require);
        self::assertSame('^2.0', $composerObject->require['hamcrest/hamcrest-php']);

        self::assertObjectHasAttribute('autoload-dev', $composerObject);
        self::assertObjectHasAttribute('psr-4', $composerObject->{'autoload-dev'});
        self::assertIsArray($composerObject->{'autoload-dev'}->{'psr-4'});
        self::assertCount(1, $composerObject->{'autoload-dev'}->{'psr-4'});
    }

    public function testThatMyComposerJsonIsCorrect_withObjectAsserter(): void
    {
        $composerObject = $this->getComposerJsonDecoded();

        $this->assertThatObject($composerObject)
            ->property('name')->contains('object-asserter')->end()
            ->property('require')->isArray()->length(1)
                ->key("amcrest/hamcrest-php", "^2.0")->end()
            ->end()
            ->property('autoload-dev')
                ->property('psr-4')->isArray()->length(1)->end()
            ->end();
    }

    protected function assertThatObject(stdClass $object): \Webforge\ObjectAsserter\ObjectAsserter
    {
        return new ObjectAsserter($object);
    }

    protected function getComposerJsonDecoded(): stdClass
    {
        return (object) [
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
        ];
    }
}
