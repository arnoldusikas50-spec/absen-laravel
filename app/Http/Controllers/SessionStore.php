<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * get/set/has/remove/destroy/regenerate/flash/flashed.
 */
class SessionStore
{
    protected ?Request $requestOverride = null;

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return session()->all();
        }

        return session()->get($key, $default);
    }

    public function set($key, $value = null): void
    {
        if (is_array($key)) {
            session()->put($key);

            return;
        }
        session()->put([$key => $value]);
    }

    public function has($key): bool
    {
        return session()->has($key);
    }

    public function remove($key): void
    {
        session()->forget($key);
    }

    public function destroy(): void
    {
        try {
            auth()->logout();
        } catch (\Throwable $e) {
        }
        session()->invalidate();
    }

    public function regenerate(bool $destroy = false): void
    {
        session()->regenerate();
    }

    public function flash($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                session()->flash($k, $v);
            }

            return;
        }
        session()->flash($key, $value);
    }

    public function flashed($key = null)
    {
        if ($key === null) {
            return session()->all();
        }

        return session()->get($key);
    }

    public function push($key, $value): void
    {
        session()->push($key, $value);
    }

    public function __get($key)
    {
        if ($key === 'request') {
            return $this->requestOverride ?? request();
        }

        return $this->get($key);
    }

    public function __set($key, $value): void
    {
        if ($key === 'request' && $value instanceof Request) {
            $this->requestOverride = $value;

            return;
        }

        $this->set($key, $value);
    }
}
