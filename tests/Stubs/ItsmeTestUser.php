<?php

namespace ItsmeLaravel\Itsme\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal User model used by the feature tests.
 *
 * Fully mass-assignable and bound to the `users` table so the package's
 * create/update logic can be exercised without depending on the host app.
 */
class ItsmeTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = true;
}