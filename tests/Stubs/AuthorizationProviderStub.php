<?php

namespace Picanova\Api\Tests\Stubs;

use Picanova\Api\Auth\Provider\Authorization;
use Picanova\Api\Routing\Route;
use Illuminate\Http\Request;

class AuthorizationProviderStub extends Authorization
{
    public function authenticate(Request $request, Route $route)
    {
        $this->validateAuthorizationHeader($request);
    }

    public function getAuthorizationMethod()
    {
        return 'foo';
    }
}
