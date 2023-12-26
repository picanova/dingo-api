<?php

namespace Picanova\Api\Contract\Http;

use Illuminate\Http\Request as IlluminateRequest;

interface Request
{
    /**
     * Create a new Dingo request instance from an Illuminate request instance.
     *
     * @param \Illuminate\Http\Request $old
     *
     * @return \Picanova\Api\Http\Request
     */
    public function createFromIlluminate(IlluminateRequest $old);
}
