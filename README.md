# Whats this?

Have you ever tried to write tests in phpunit, that do assert a lot of simple properties, and nested array or object structures?  
Imagine you get this json structure:

```php
<?php
$composerObject = (object) [
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
```

But you want only assert certain parts of the composer.json object. You learned a lot about assertions yet, and you know just asserting that the composer.json is equal my fixture will make this test very hard to maintain. What if you only want to assert parts of this?

And then you want assert that:
- the structure is an object
- which contains a property name
- this property is in turn an string
- that string contains something
- the root object has a key require
- which is a array
- with a string key
- that contains a regexp

Lets see, what the phpunit code would look like:

```php
    public function testThatMyComposerJsonIsCorrect_withPhpunit(): void
    {
        $composerObject = ... // same as above

        self::assertIsObject($composerObject);

        self::assertObjectHasAttribute('name', $composerObject);
        self::assertIsString($composerObject->name);
        self::assertStringContainsString('object-asserter', $composerObject->name);

        self::assertObjectHasAttribute('require', $composerObject);
        self::assertIsArray($composerObject->require);
        self::assertCount(1, $composerObject->require);
        self::assertArrayHasKey('hamcrest/hamcrest-php', $composerObject->require);
        self::assertSame('^2.0', $composerObject->require['hamcrest/hamcrest-php']);

        self::assertObjectHasAttribute('autoload-dev', $composerObject);
        self::assertObjectHasAttribute('psr-4', $composerObject->{'autoload-dev'});
        self::assertIsArray($composerObject->{'autoload-dev'}->{'psr-4'});
        self::assertCount(1, $composerObject->{'autoload-dev'}->{'psr-4'});
    }
```

That's not too bad isnt it?

But.. (there is always the but isnt it?). Is this really a good test? Can you read it? Can you follow what it asserts? Try to run it: does it really communicate exactly whats failing?  
When we are writing tests, we are often in a rush, and we forgot to "do it right". E. g. whats with all the missing messages here?

```
$ phpunit docs/examples/Example.php
PHPUnit 9.5.6 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.4.15
Configuration: /app/phpunit.xml

.F                                                                  2 / 2 (100%)

Time: 00:00.003, Memory: 6.00 MB

There was 1 failure:

1) Example::testThatMyComposerJsonIsCorrect_withPhpunit
Failed asserting that an array has the key 'amcrest/hamcrest-php'.

/app/docs/examples/Example.php:23
```

Okay, so one of my assertions failed. But on which part? Ah let me open that test and hop to Line 23 and see...
Nope. Dont do that. Do your collegues a favor and let them know what's failing, without looking INTO the test:

Lets have a look what the object asserter message would look like:

```
$ phpunit docs/examples/Example.php
PHPUnit 9.5.6 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.4.15
Configuration: /app/phpunit.xml

FE                                                                  2 / 2 (100%)

Time: 00:00.005, Memory: 6.00 MB

There was 1 error:

1) Example::testThatMyComposerJsonIsCorrect_withObjectAsserter
Hamcrest\AssertionError: $root.require does not have key amcrest/hamcrest-php
Expected: array with key "amcrest/hamcrest-php"
     but: array was ["hamcrest/hamcrest-php" => "^2.0"]

/app/vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest/MatcherAssert.php:115
/app/vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest/MatcherAssert.php:63
/app/src/ObjectAsserter.php:294
/app/src/ObjectAsserter.php:160
/app/docs/examples/Example.php:39
```

Dont get me wrong: I was always a fan of phpunit, but these Hamcrest messages (the [underlying library](https://github.com/hamcrest/hamcrest-php) that I use for assertions), is just the better one in this.    
And noticed the: `$root.require does not have key amcrest/hamcrest-php` This is how you tell your fellow, that is debugging the failing test on the pipeline: hey: the require property in the composer.json has changed and has a typo.

Now finally, lets see how we would write that test with the object-asserter:

```php
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
```

Can you read that? Yes its a big ugly with those end()s, but at least you can read it now. And believe me: I used this everywhere and people just "get it" how it works. And I keep my promise, that the failing assertion-messages are nice, believe me ;)

# How to use this library

```
composer require --dev webforge/object-asserter
```

Create you some base method in your TestCase. Or you can use the AssertionsTrait provided. (Look at the examples)

## api

in all cases where you can pass a `mixed $matcher` you can either pass an `\Hamcrest\Matcher` or just a primitve which will then be wrapped as `\Hamcrest\Matchers::equalTo($primitive)`
That way we get the power from all the [hamcrest assertions](https://github.com/hamcrest/hamcrest-php)

### property(string $name, mixed $matcher = null)

Asserts that the parent is an object and that is has a property named $name. if $matcher is set, it will assert the VALUE of the property against the matcher.

### key(string|int $index, mixed $matcher = null)

Asserts that the parent is an array containg the key $index, and its value matches $matcher.

### end()

When tapped into a property, you are now in the context of the value of the property, allowing you to tap into deeper chains of your object. With end() you go one level up:

```php
$example = [
   'level1'=>[
     'level2'=>true
   ]
]

$this->assertThatArray($example)
  ->key('level1')   // now we do assertions on ['level2'=>true]
  ->end()           // now we are back at the root: ['level1'=>['level2'=>true]]  
```

### contains(string $needle)

Asserts that the current context is a string and that it contains $needle

use Hamcrest\Matchers::containsString() for that

### length(int $length)

Asserts that the current context is an array of length $length

### debug()

Will dump the *current* context with var_dump() into the console. Good for the poor mans debugging, when you assert structures that are million lines long

### get()

Returns the current context and stops the chaining

### tap(function($data, ObjectAsserter $objectAsserter))

Taps into the current context, without stopping the chain, you get the $value as first argument, and the context as second

```php
$this->assertThatObject($composerObject)
    ->property('authors')
        ->tap(function(array $authors) {
            // do whatever you like now, do phpunit assertions, or normalize, or, or 
            self::assertArrayHasKey(0, $authors);
        })
        ->key(0) // is still in context of the property authors
```

### is(mixed $matcher), isNot(mixed $matcher)

Asserts on the current context. Or negative assert on the current context

### isNotEmptyString()

Asserts that the current context is a string and that its not empty.

### equals8601Date(\DateTimeInterface $expectedDate)

Asserts that current context is a string representing an Iso8601 date in the format `Y-m-d`, that is equal (same day) as the $expectedDate.

### properties(array $indexes, mixed $matcher): ObjectAsserter

Asserts that the current context is an array, that has $index in $indexes.  
For each $index it gets the value from the array at this $index and matches against the $matcher.

Use this for quick wins:
```php
$this->assertThatObject($composerObject)
    ->properties(['name', 'description', 'type'], Matchers::nonEmptyString());
```

## Some more examples

```php
    public function testThatMyValidationFactorReturnsTheRemoteAddress(): void
    {
        $response = json_decode(<<<'JSON'
{
   "username" : "my_username",
   "password" : "my_password",
   "validation-factors" : {
      "validationFactors" : [
         {
            "name" : "remote_address",
            "value" : "127.0.0.1"
         }
      ]
   }
}
JSON
        );

        $factor0 = $this->assertThatObject($response)
            ->property('username')->contains('my_')->end()
            ->property('validation-factors')
                ->property('validationFactors')->isArray()
                    ->key(0)
                        ->property('name')->is(\Hamcrest\Matchers::equalToIgnoringCase('Remote_Address'))->end()
                    ->get();

        self::assertMatchesRegularExpression(
            '/(\d+\.){3}\d+/',
            $factor0->value,
        );
    }

```

```php
  $this->assertThatObject($pbMeta = $binary->getMediaMetadata('focals.v1'))
    ->property('faces')->isArray()->length(2) // this might change and is okay, if algorithm improves
        ->key(0)
            ->property('confidence')->is(Matchers::greaterThan(0.4))->end()
        ->end()
        ->key(1)
            ->property('confidence')->is(Matchers::greaterThan(0.4))->end()
        ->end()
    ;
```

```php
$this->assertJsonResponseContent(400, $this->client)
    ->property('detail', 'Nicht bestellbar: Die minimale Anzahl für dieses Format sind 13 Doppelseiten')->end()
    ->property('suggestion', 'Füge mehr Einträge hinzu.')->end();

$this->assertJsonResponseContent(403, $this->client)
    ->property('type', 'http://ps-webforge.net/rfc/redirect')->end()
    ->property('detail', Matchers::containsString('ist abgeschlossen'))->end()
    ->property('href', Matchers::containsString('photobook/' . $photobook->getId() . '/print'))->end();
```

Have fun writing tests!

# License

MIT License

Copyright (c) 2021 webforge <p.scheit@ps-webforge.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

