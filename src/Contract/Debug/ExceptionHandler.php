<?php

namespace Picanova\Api\Contract\Debug;

interface ExceptionHandler
{
    /**
     * Handle an exception.
     *
     * @param \Throwable|\Exception $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function handle($exception);
}
