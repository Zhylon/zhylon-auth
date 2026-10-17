<?php

namespace Zhylon\ZhylonAuth\Tests\Fixtures;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use \Illuminate\Auth\MustVerifyEmail;

    protected $table = 'users';

    protected $guarded = [];
}
