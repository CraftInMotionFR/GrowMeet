<?php

class Router {
    /** @var Route[] */
    private array $routes = [];

    // Enregistrement des routes
    public function get(string $path, string $controller, string $action): Route {
        $route = new Route('GET', $path, $controller, $action);
        $this->routes[] = $route;
        return $route;
    }
    public function post(string $path, string $controller, string $action): Route {
        $route = new Route('POST', $path, $controller, $action);
        $this->routes[] = $route;
        return $route;
    }
    // Résolution
    public function dispatch(string $method, string $url): void {
        foreach ($this->routes as $route) {
            $params = $route->match($method, $url);
            if ($params !== null) {
                $this->callAction($route, $params);
                return;
            }
        }
        $this->notFound();
    }

    // Instanciation contrôleur + appel action
    private function callAction(Route $route, array $params): void {
        $this->checkMiddleware($route->getMiddleware());
        $controllerName = $route->getController();
        $action = $route->getAction();
        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }
        $controller = new $controllerName();
        if (!method_exists($controller, $action)) {
            $this->notFound();
            return;
        }
        call_user_func_array([$controller, $action], $params);
    }

    private function checkMiddleware(?string $middleware): void {
        if ($middleware === null) return;
        $isLoggedIn = isset($_SESSION['user']);
        match($middleware) {
            // Route protégée,doit être connecté
            'auth' => $isLoggedIn ?: $this->redirectTo('/login'),
            // Route invité, ne doit pas être connecté
            'guest' => !$isLoggedIn ?: $this->redirectTo($this->homeUrlForRole($_SESSION['user']['role'] ?? null)),

            default => null,
        };
    }

    // Page d'accueil selon le rôle : les admins/coachs n'ont pas de dashboard "chiens"
    private function homeUrlForRole(?string $role): string {
        return match ($role) {
            'administrator' => '/admin/course-types',
            'coach' => '/admin/sessions',
            default => '/dashboard',
        };
    }

    private function redirectTo(string $url): never {
        header("Location: {$url}");
        exit;
    }


    // Fallback 404
    private function notFound(): void {
        http_response_code(404);

        // Utilise le système de vues si possible, sinon message brut
        if (class_exists('Controller')) {
            $fallback = new class extends Controller {
                public function show(): void { 
                    $this->notFound(); 
                    }
            };
            $fallback->show();
        } else {
            echo "<h1>Erreur 404 : Page non trouvée</h1>";
        }
        exit;
    }
}
