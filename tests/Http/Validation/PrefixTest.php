<?php

namespace Picanova\Api\Tests\Http\Validation;

use Picanova\Api\Http\Validation\Prefix;
use Picanova\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;

class PrefixTest extends BaseTestCase
{
    public function testValidationFailsWithInvalidOrNullPrefix()
    {
        $validator = new Prefix('foo');
        $this->assertFalse($validator->validate(Request::create('bar', 'GET')), 'Validation passed when it should have failed with an invalid prefix.');

        $validator = new Prefix(null);
        $this->assertFalse($validator->validate(Request::create('foo', 'GET')), 'Validation passed when it should have failed with a null prefix.');
    }

    public function testValidationPasses()
    {
        $validator = new Prefix('foo');
        $this->assertTrue($validator->validate(Request::create('foo', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
        $this->assertTrue($validator->validate(Request::create('foo/bar', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
    }

    public function testValidationPassesWithHyphenatedPrefix()
    {
        $validator = new Prefix('web-api');
        $this->assertTrue($validator->validate(Request::create('web-api', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
        $this->assertTrue($validator->validate(Request::create('web-api/bar', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
    }
}
