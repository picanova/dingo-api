<?php

namespace Picanova\Api\Tests\Http\Response\Format;

use Picanova\Api\Http\Response;
use Picanova\Api\Http\Response\Format\Jsonp;
use Picanova\Api\Tests\BaseTestCase;
use Picanova\Api\Tests\Stubs\EloquentModelStub;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

class JsonpTest extends BaseTestCase
{
    public function setUp(): void
    {
        $formatter = new Jsonp;
        $formatter->setRequest(Request::create('GET', '/', ['callback' => 'foo']));

        Response::setFormatters(['json' => $formatter]);
    }

    /*
     * Read expected pretty printed JSONP string from external file.
     *
     * JSONP strings, that are expected for assertion in each test, are placed
     * in separate files to avoid littering tests and available on demand.
     * All the filenames are the same as the tests they associated to.
     *
     * @return string
     */
    private function getExpectedPrettyPrintedJsonp($testMethodName)
    {
        return require __DIR__.DIRECTORY_SEPARATOR.
            'ExpectedPrettyPrintedJsonp'.DIRECTORY_SEPARATOR.
            $testMethodName.'.jsonp.php';
    }

    public function testMorphingEloquentModel()
    {
        $response = (new Response(new EloquentModelStub))->morph();

        $this->assertSame('foo({"foo_bar":{"foo":"bar"}});', $response->getContent());
    }

    public function testMorphingEloquentCollection()
    {
        $response = (new Response(new Collection([new EloquentModelStub, new EloquentModelStub])))->morph();

        $this->assertSame('foo({"foo_bars":[{"foo":"bar"},{"foo":"bar"}]});', $response->getContent());
    }

    public function testMorphingEmptyEloquentCollection()
    {
        $response = (new Response(new Collection))->morph();

        $this->assertSame('foo([]);', $response->getContent());
    }

    public function testMorphingString()
    {
        $response = (new Response('foo'))->morph();

        $this->assertSame('foo', $response->getContent());
    }

    public function testMorphingArray()
    {
        $messages = new MessageBag(['foo' => 'bar']);

        $response = (new Response(['foo' => 'bar', 'baz' => $messages]))->morph();

        $this->assertSame('foo({"foo":"bar","baz":{"foo":["bar"]}});', $response->getContent());
    }

    public function testMorphingUnknownType()
    {
        $this->assertSame(1, (new Response(1))->morph()->getContent());
    }

    public function testMorphingArrayWithOneTabPrettyPrintIndent()
    {
        $options = [
            'json' => [
                'pretty_print' => true,
                'indent_style' => 'tab',
            ],
        ];

        Response::setFormatsOptions($options);

        $array = ['foo' => 'bar', 'baz' => ['foobar' => [42, 0.00042, '', null]]];
        $response = (new Response($array))->morph();

        $this->assertSame($this->getExpectedPrettyPrintedJsonp(__FUNCTION__), $response->getContent());
    }

    public function testMorphingArrayWithTwoSpacesPrettyPrintIndent()
    {
        $options = [
            'json' => [
                'pretty_print' => true,
                'indent_style' => 'space',
                'indent_size' => 2,
            ],
        ];

        Response::setFormatsOptions($options);

        $array = ['foo' => 'bar', 'baz' => ['foobar' => [42, 0.00042, '', null]]];
        $response = (new Response($array))->morph();

        $this->assertSame($this->getExpectedPrettyPrintedJsonp(__FUNCTION__), $response->getContent());
    }

    public function testMorphingArrayWithFourSpacesPrettyPrintIndent()
    {
        $options = [
            'json' => [
                'pretty_print' => true,
                'indent_style' => 'space',
                'indent_size' => 4,
            ],
        ];

        Response::setFormatsOptions($options);

        $array = ['foo' => 'bar', 'baz' => ['foobar' => [42, 0.00042, '', null]]];
        $response = (new Response($array))->morph();

        $this->assertSame($this->getExpectedPrettyPrintedJsonp(__FUNCTION__), $response->getContent());
    }

    public function testMorphingArrayWithEightSpacesPrettyPrintIndent()
    {
        $options = [
            'json' => [
                'pretty_print' => true,
                'indent_style' => 'space',
                'indent_size' => 8,
            ],
        ];

        Response::setFormatsOptions($options);

        $array = ['foo' => 'bar', 'baz' => ['foobar' => [42, 0.00042, '', null]]];
        $response = (new Response($array))->morph();

        $this->assertSame($this->getExpectedPrettyPrintedJsonp(__FUNCTION__), $response->getContent());
    }
}
