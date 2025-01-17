<?php

namespace Picanova\Api\Tests;

use Picanova\Api\Auth\Auth;
use Picanova\Api\Dispatcher;
use Picanova\Api\Exception\Handler;
use Picanova\Api\Exception\InternalHttpException;
use Picanova\Api\Exception\ValidationHttpException;
use Picanova\Api\Http;
use Picanova\Api\Http\Response;
use Picanova\Api\Routing\Router;
use Picanova\Api\Tests\Stubs\MiddlewareStub;
use Picanova\Api\Tests\Stubs\RoutingAdapterStub;
use Picanova\Api\Tests\Stubs\TransformerStub;
use Picanova\Api\Tests\Stubs\UserStub;
use Picanova\Api\Tests\Stubs\UserTransformerStub;
use Picanova\Api\Transformer\Factory as TransformerFactory;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class DispatcherTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var TransformerFactory
     */
    protected $transformerFactory;
    /**
     * @var Dispatcher
     */
    protected $dispatcher;
    /**
     * @var RoutingAdapterStub
     */
    protected $adapter;
    /**
     * @var Handler|m\LegacyMockInterface|m\MockInterface
     */
    protected $exception;
    /**
     * @var Router
     */
    protected $router;
    /**
     * @var Auth
     */
    protected $auth;

    public function setUp(): void
    {
        $this->container = new Container;
        $this->container['request'] = Request::create('/', 'GET');
        $this->container['api.auth'] = new MiddlewareStub;
        $this->container['api.limiting'] = new MiddlewareStub;

        Http\Request::setAcceptParser(new Http\Parser\Accept('vnd', 'api', 'v1', 'json'));

        $this->transformerFactory = new TransformerFactory($this->container, new TransformerStub);

        $this->adapter = new RoutingAdapterStub;
        $this->exception = m::mock(Handler::class);
        $this->router = new Router($this->adapter, $this->exception, $this->container, null, null);

        $this->auth = new Auth($this->router, $this->container, []);
        $this->dispatcher = new Dispatcher($this->container, new Filesystem, $this->router, $this->auth);

        app()->instance(\Illuminate\Routing\Router::class, $this->adapter);

        $this->dispatcher->setSubtype('api');
        $this->dispatcher->setStandardsTree('vnd');
        $this->dispatcher->setDefaultVersion('v1');
        $this->dispatcher->setDefaultFormat('json');

        Response::setFormatters(['json' => new Http\Response\Format\Json]);
        Response::setTransformer($this->transformerFactory);
    }

    public function testInternalRequests()
    {
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                return 'foo';
            });

            $this->router->post('test', function () {
                return 'bar';
            });

            $this->router->put('test', function () {
                return 'baz';
            });

            $this->router->patch('test', function () {
                return 'yin';
            });

            $this->router->delete('test', function () {
                return 'yang';
            });
        });

        $this->assertSame('foo', $this->dispatcher->get('test'));
        $this->assertSame('bar', $this->dispatcher->post('test'));
        $this->assertSame('baz', $this->dispatcher->put('test'));
        $this->assertSame('yin', $this->dispatcher->patch('test'));
        $this->assertSame('yang', $this->dispatcher->delete('test'));
    }

    public function testInternalRequestWithVersionAndParameters()
    {
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertSame('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
    }

    public function testInternalRequestWithPrefix()
    {
        $this->router->version('v1', ['prefix' => 'baz'], function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertSame('test', $this->dispatcher->get('baz/test'));

        $this->dispatcher->setPrefix('baz');

        $this->assertSame('test', $this->dispatcher->get('test'));
    }

    public function testInternalRequestWithDomain()
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('test', function () {
                return 'test';
            });
        });

        $this->assertSame('test', $this->dispatcher->get('http://foo.bar/test'));

        $this->dispatcher->setDefaultDomain('foo.bar');

        $this->assertSame('test', $this->dispatcher->get('test'));
    }

    public function testInternalRequestThrowsExceptionWhenResponseIsNotOkay()
    {
        $this->expectException(InternalHttpException::class);

        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                return new \Illuminate\Http\Response('test', 403);
            });
        });

        $this->dispatcher->get('test');
    }

    public function testInternalExceptionContainsResponseObject()
    {
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                return new \Illuminate\Http\Response('test', 401);
            });
        });

        try {
            $this->dispatcher->get('test');
        } catch (InternalHttpException $exception) {
            $this->assertInstanceOf(\Illuminate\Http\Response::class, $exception->getResponse());
            $this->assertSame('test', $exception->getResponse()->getContent());
        }
    }

    public function testThrowingHttpExceptionFallsThroughRouter()
    {
        $this->router->version('v1', function () {
            $this->router->get('test', function () {
                throw new GoneHttpException;
            });
        });

        $passed = false;

        try {
            $this->dispatcher->get('test');
        } catch (GoneHttpException $exception) {
            $passed = true;
        }

        $this->assertTrue($passed);
    }

    public function testPretendingToBeUserForSingleRequest()
    {
        $user = m::mock('Illuminate\Database\Eloquent\Model');

        $this->router->version('v1', function () use ($user) {
            $this->router->get('test', function () use ($user) {
                $this->assertSame($user, $this->auth->user());
            });
        });

        $this->dispatcher->be($user)->once()->get('test');
    }

    public function testInternalRequestWithMultipleVersionsCallsCorrectVersion()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version(['v2', 'v3'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->assertSame('foo', $this->dispatcher->version('v1')->get('foo'));
        $this->assertSame('bar', $this->dispatcher->version('v2')->get('foo'));
        $this->assertSame('bar', $this->dispatcher->version('v3')->get('foo'));
    }

    public function testInternalRequestWithNestedInternalRequest()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo'.$this->dispatcher->version('v2')->get('foo');
            });
        });

        $this->router->version('v2', function () {
            $this->router->get('foo', function () {
                return 'bar'.$this->dispatcher->version('v3')->get('foo');
            });
        });

        $this->router->version('v3', function () {
            $this->router->get('foo', function () {
                return 'baz';
            });
        });

        $this->assertSame('foobarbaz', $this->dispatcher->get('foo'));
    }

    public function testRequestStackIsMaintained()
    {
        $this->router->version('v1', ['prefix' => 'api'], function () {
            $this->router->post('foo', function () {
                $this->assertSame('bar', $this->router->getCurrentRequest()->input('foo'));
                $this->dispatcher->with(['foo' => 'baz'])->post('api/bar');
                $this->assertSame('bar', $this->router->getCurrentRequest()->input('foo'));
            });

            $this->router->post('bar', function () {
                $this->assertSame('baz', $this->router->getCurrentRequest()->input('foo'));
                $this->dispatcher->with(['foo' => 'bazinga'])->post('api/baz');
                $this->assertSame('baz', $this->router->getCurrentRequest()->input('foo'));
            });

            $this->router->post('baz', function () {
                $this->assertSame('bazinga', $this->router->getCurrentRequest()->input('foo'));
            });
        });

        $this->dispatcher->with(['foo' => 'bar'])->post('api/foo');
    }

    public function testRouteStackIsMaintained()
    {
        $this->router->version('v1', function () {
            $this->router->post('foo', ['as' => 'foo', function () {
                $this->assertSame('foo', $this->router->getCurrentRoute()->getName());
                $this->dispatcher->post('bar');
                $this->assertSame('foo', $this->router->getCurrentRoute()->getName());
            }]);

            $this->router->post('bar', ['as' => 'bar', function () {
                $this->assertSame('bar', $this->router->getCurrentRoute()->getName());
                $this->dispatcher->post('baz');
                $this->assertSame('bar', $this->router->getCurrentRoute()->getName());
            }]);

            $this->router->post('baz', ['as' => 'bazinga', function () {
                $this->assertSame('bazinga', $this->router->getCurrentRoute()->getName());
            }]);
        });

        $this->dispatcher->post('foo');
    }

    public function testSendingJsonPayload()
    {
        $this->router->version('v1', function () {
            $this->router->post('foo', function () {
                $this->assertSame('jason', $this->router->getCurrentRequest()->json('username'));
            });

            $this->router->post('bar', function () {
                $this->assertSame('mat', $this->router->getCurrentRequest()->json('username'));
            });
        });

        $this->dispatcher->json(['username' => 'jason'])->post('foo');
        $this->dispatcher->json('{"username":"mat"}')->post('bar');
    }

    public function testInternalRequestsToDifferentDomains()
    {
        $this->router->version(['v1', 'v2'], ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'v1 and v2 on domain foo.bar';
            });
        });

        $this->router->version('v1', ['domain' => 'foo.baz'], function () {
            $this->router->get('foo', function () {
                return 'v1 on domain foo.baz';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.baz'], function () {
            $this->router->get('foo', function () {
                return 'v2 on domain foo.baz';
            });
        });

        $this->assertSame('v1 and v2 on domain foo.bar', $this->dispatcher->on('foo.bar')->version('v2')->get('foo'));
        $this->assertSame('v1 on domain foo.baz', $this->dispatcher->on('foo.baz')->get('foo'));
        $this->assertSame('v2 on domain foo.baz', $this->dispatcher->on('foo.baz')->version('v2')->get('foo'));
    }

    public function testRequestingRawResponse()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return ['foo' => 'bar'];
            });
        });

        $response = $this->dispatcher->raw()->get('foo');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame(['foo' => 'bar'], $response->getOriginalContent());
    }

    public function testRequestingRawResponseWithTransformers()
    {
        $instance = null;

        $this->router->version('v1', function () use (&$instance) {
            $this->router->get('foo', function () use (&$instance) {
                return $instance = new UserStub('Jason');
            });
        });

        $this->transformerFactory->register(UserStub::class, UserTransformerStub::class);

        $response = $this->dispatcher->raw()->get('foo');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('{"name":"Jason"}', $response->getContent());
        $this->assertSame($instance, $response->getOriginalContent());
    }

    public function testUsingRequestFacadeDoesNotCacheRequestInstance()
    {
        RequestFacade::setFacadeApplication($this->container);

        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return RequestFacade::input('foo');
            });
        });

        $this->assertNull(RequestFacade::input('foo'));

        $response = $this->dispatcher->with(['foo' => 'bar'])->get('foo');

        $this->assertSame('bar', $response);
        $this->assertNull(RequestFacade::input('foo'));
    }

    public function testRedirectResponseThrowsException()
    {
        $this->router->version('v1', function () {
            $this->router->get('redirect', function () {
                return new RedirectResponse('redirect-test');
            });
        });

        $response = $this->dispatcher->get('redirect');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('redirect-test', $response->getTargetUrl());
    }

    public function testNotOkJsonResponseThrowsException()
    {
        $this->expectException(InternalHttpException::class);

        $this->router->version('v1', function () {
            $this->router->get('json', function () {
                return new JsonResponse(['is' => 'json'], 422);
            });
        });

        $this->dispatcher->get('json');
    }

    public function testFormRequestValidationFailureThrowsValidationException()
    {
        $this->expectException(ValidationHttpException::class);

        $this->router->version('v1', function () {
            $this->router->get('fail', function () {
                //Mocking the form validation call is challenging at the moment, so next best thing
                throw new ValidationHttpException(['foo' => 'bar']);
            });
        });

        $this->dispatcher->get('fail');
    }
}
