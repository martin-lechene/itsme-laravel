<?php

namespace ItsmeLaravel\Itsme\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItsmeAuthenticationFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $error,
        public ?string $errorDescription = null
    ) {
    }
}

