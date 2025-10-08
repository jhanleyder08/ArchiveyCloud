<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TwoFactorAuthenticationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $action;
    public ?string $method;
    public bool $success;
    public ?string $ipAddress;
    public ?string $userAgent;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $user,
        string $action,
        ?string $method = null,
        bool $success = true,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->method = $method;
        $this->success = $success;
        $this->ipAddress = $ipAddress ?? request()->ip();
        $this->userAgent = $userAgent ?? request()->userAgent();
    }
}
