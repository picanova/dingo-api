<?php

namespace Picanova\Api\Tests\Stubs;

class UserTransformerStub
{
    public function transform(UserStub $user)
    {
        return [
            'name' => $user->name,
        ];
    }
}
