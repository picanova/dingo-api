<?php

namespace Picanova\Api\Tests\Stubs;

use Picanova\Api\Contract\Http\Validator;
use Illuminate\Http\Request;

class HttpValidatorStub implements Validator
{
    public function validate(Request $request)
    {
        return $request->getMethod() === 'POST';
    }
}
