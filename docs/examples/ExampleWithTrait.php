<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Webforge\ObjectAsserter\ObjectAsserter;

class ExampleWithTrait extends TestCase
{
    use \Webforge\ObjectAsserter\AssertionsTrait;

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
}
