<?php

class Route {
    private ?string $middleware = null; // 'auth', 'guest', ou null

    public function __construct(
        private string $method,
        private string $path,
        private string $controller,
        private string $action
    ) {}

    public function getMethod(): string      { return $this->method; }
    public function getPath(): string        { return $this->path; }
    public function getController(): string  { return $this->controller; }
    public function getAction(): string      { return $this->action; }
    public function getMiddleware(): ?string { return $this->middleware; }

    public function middleware(string $name): static {
        $this->middleware = $name;
        return $this;
    }

    public function match(string $method, string $url): ?array {
        if ($this->method !== $method) return null;

        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '([^/]+)', $this->path);
        $pattern = "#{$pattern}#";

        if (!preg_match($pattern, $url, $matches)) return null;

        array_shift($matches);
        return $matches;
    }
}