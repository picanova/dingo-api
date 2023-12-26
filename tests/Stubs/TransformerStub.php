<?php

namespace Picanova\Api\Tests\Stubs;

use Picanova\Api\Contract\Transformer\Adapter;
use Picanova\Api\Http\Request;
use Picanova\Api\Transformer\Binding;
use Illuminate\Support\Collection;

class TransformerStub implements Adapter
{
    public function transform($response, $transformer, Binding $binding, Request $request)
    {
        if ($response instanceof Collection) {
            return $response->transform(function ($response) use ($transformer) {
                return $transformer->transform($response);
            })->toArray();
        }

        return $transformer->transform($response);
    }
}
