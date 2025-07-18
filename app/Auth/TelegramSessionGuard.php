<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;

abstract class TelegramSessionGuard implements Guard
{
    protected ?\Illuminate\Contracts\Auth\Authenticatable $user = null;
    protected UserProvider $provider;
    protected Request $request;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function user()
    {
        if ($this->user === null) {
            if ($tid = session('telegram_user_id')) {
                $this->user = $this->provider->retrieveById($tid);
            }
        }
        return $this->user;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function check(): bool
    {
        return $this->hasUser();
    }

    public function guest(): bool
    {
        return !$this->hasUser();
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->user = $user;
        return $this;
    }
}

