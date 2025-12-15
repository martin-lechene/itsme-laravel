<?php

namespace ItsmeLaravel\Itsme\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItsmeUserAuthenticated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public $user,
        public array $userInfo
    ) {
    }
}

