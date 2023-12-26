<?php

namespace Picanova\Api\Tests\Transformer\Adapter;

use Picanova\Api\Http\Request;
use Picanova\Api\Tests\BaseTestCase;
use Picanova\Api\Transformer\Adapter\Fractal;
use League\Fractal\Manager as FractalManager;

class FractalTest extends BaseTestCase
{
    protected $fractal;

    public function setUp(): void
    {
        $this->fractal = new Fractal(new FractalManager());
    }

    public function testParseFractalIncludes()
    {
        $request = Request::create('/?include=foo,bar', 'GET');
        $this->fractal->parseFractalIncludes($request);
        $requestedIncludes = $this->fractal->getFractal()->getRequestedIncludes();

        $this->assertEquals(['foo', 'bar'], $requestedIncludes);
    }

    public function testParseFractalIncludesWithSpaces()
    {
        $request = Request::create('/?include=foo, bar', 'GET');
        $this->fractal->parseFractalIncludes($request);
        $requestedIncludes = $this->fractal->getFractal()->getRequestedIncludes();

        $this->assertEquals(['foo', 'bar'], $requestedIncludes);
    }
}
