<?php

namespace Picanova\Api\Tests\Stubs;

use Illuminate\Routing\Controller;

class RoutingControllerOtherStub extends Controller
{
    public function find()
    {
        return 'baz';
    }
}
