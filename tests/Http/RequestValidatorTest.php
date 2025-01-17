<?php

namespace Picanova\Api\Tests\Http;

use Picanova\Api\Http\Parser\Accept as AcceptParser;
use Picanova\Api\Http\RequestValidator;
use Picanova\Api\Tests\BaseTestCase;
use Picanova\Api\Tests\Stubs\HttpValidatorStub;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

class RequestValidatorTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var RequestValidator
     */
    protected $validator;

    public function setUp(): void
    {
        $this->container = new Container;
        $this->container->instance(AcceptParser::class, new AcceptParser('vnd', 'test', 'v1', 'json'));
        $this->validator = new RequestValidator($this->container);
    }

    public function testValidationFailsWithNoValidators()
    {
        $this->validator->replace([]);

        $this->assertFalse($this->validator->validateRequest(Request::create('foo', 'GET')), 'Validation passed when there were no validators.');
    }

    public function testValidationFails()
    {
        $this->validator->replace([HttpValidatorStub::class]);

        $this->assertFalse($this->validator->validateRequest(Request::create('foo', 'GET')), 'Validation passed when given a GET request.');
    }

    public function testValidationPasses()
    {
        $this->validator->replace([HttpValidatorStub::class]);

        $this->assertTrue($this->validator->validateRequest(Request::create('foo', 'POST')), 'Validation failed when given a POST request.');
    }
}
