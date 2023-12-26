<?php

namespace Picanova\Api\Contract\Transformer;

use Picanova\Api\Http\Request;
use Picanova\Api\Transformer\Binding;

interface Adapter
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed                          $response
     * @param object                         $transformer
     * @param \Picanova\Api\Transformer\Binding $binding
     * @param \Picanova\Api\Http\Request        $request
     *
     * @return array
     */
    public function transform($response, $transformer, Binding $binding, Request $request);
}
