<?php

namespace Picanova\Api\Http\Middleware;

use Closure;
use Picanova\Api\Routing\Router;
use Picanova\Api\Auth\Auth as Authentication;

class Auth
{
    /**
     * Router instance.
     *
     * @var \Picanova\Api\Routing\Router
     */
    protected $router;

    /**
     * Authenticator instance.
     *
     * @var \Picanova\Api\Auth\Auth
     */
    protected $auth;

    /**
     * Create a new auth middleware instance.
     *
     * @param \Picanova\Api\Routing\Router $router
     * @param \Picanova\Api\Auth\Auth      $auth
     *
     * @return void
     */
    public function __construct(Router $router, Authentication $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }

    /**
     * Perform authentication before a request is executed.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $this->router->getCurrentRoute();

        if (! $this->auth->check(false)) {
            $this->auth->authenticate($route->getAuthenticationProviders());
        }

        return $next($request);
    }
}
