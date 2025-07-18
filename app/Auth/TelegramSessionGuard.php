<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;

abstract class TelegramSessionGuard implements Guard
{
    protected Request $request;
    protected UserProvider $provider;
    protected ?\Illuminate\Contracts\Auth\Authenticatable $user = null;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function user()
    {
        if ($this->user === null) {
            $telegramId = session('telegram_user_id');
            $this->user = $telegramId
                ? $this->provider->retrieveById($telegramId)
                : null;
        }
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return $this->user() === null;
    }

    public function id(): ?int
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
