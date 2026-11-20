<?php

namespace App\Http\Controllers;

/**
 * statusCode/contentType/header/json/body/download/noCache.
 */
class ResponseBuilder
{
    protected int $status = 200;

    protected array $headers = [];

    public function statusCode(int $code): static
    {
        $this->status = $code;

        return $this;
    }

    public function contentType(string $type): static
    {
        $this->headers['Content-Type'] = $type;

        return $this;
    }

    public function header(string $name, $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function noCache(): static
    {
        $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate';

        return $this;
    }

    public function json($data)
    {
        return response()->json($data, $this->status, $this->headers);
    }

    public function body($body)
    {
        return response($body, $this->status, $this->headers);
    }

    public function download(string $path, $name = null)
    {
        return response()->download($path, $name);
    }

    public function redirect(string $uri)
    {
        return redirect()->to($uri);
    }
}
