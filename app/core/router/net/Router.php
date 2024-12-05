<?php

declare(strict_types=1);

namespace App\core\router\net;

use Exception;

/**
 * Classe Router
 *
 * Gerencia o roteamento de requisições HTTP para os callbacks definidos.
 * Permite o mapeamento de padrões de URL para métodos e o agrupamento de rotas com middlewares.
 */
class Router
{
    /**
     * Define se o roteamento deve ser case-sensitive.
     */
    public bool $case_sensitive = false;

    /**
     * Lista de rotas mapeadas.
     *
     * @var array<int, Route>
     */
    protected array $routes = [];

    /**
     * Rota atualmente executada.
     */
    public ?Route $executedRoute = null;

    /**
     * Índice atual para iteração de rotas.
     */
    protected int $index = 0;

    /**
     * Prefixo de grupo para rotas.
     */
    protected string $groupPrefix = '';

    /**
     * Middlewares aplicados ao grupo de rotas.
     *
     * @var array<int, mixed>
     */
    protected array $groupMiddlewares = [];

    /**
     * Métodos HTTP permitidos.
     *
     * @var array<int, string>
     */
    protected array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /**
     * Retorna todas as rotas mapeadas.
     *
     * @return array<int, Route>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Remove todas as rotas do roteador.
     */
    public function clear(): void
    {
        $this->routes = [];
    }

    /**
     * Mapeia um padrão de URL para um callback.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Callback associado.
     * @param bool $pass_route Indica se o objeto de rota deve ser passado para o callback.
     * @param string $route_alias Alias para a rota.
     * @return Route
     */
    public function map(string $pattern, $callback, bool $pass_route = false, string $route_alias = ''): Route
    {
        $url = $this->groupPrefix !== '' ? rtrim($this->groupPrefix . trim($pattern)) : trim($pattern);
        $methods = ['*'];

        if (strpos($url, ' ') !== false) {
            [$method, $url] = explode(' ', $url, 2);
            $methods = explode('|', $method);
            if (in_array('GET', $methods, true) && !in_array('HEAD', $methods, true)) {
                $methods[] = 'HEAD';
            }
        }

        $route = new Route($url, $callback, $methods, $pass_route, $route_alias);

        foreach ($this->groupMiddlewares as $gm) {
            $route->addMiddleware($gm);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Cria uma rota GET.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Callback associado.
     * @param bool $pass_route Indica se o objeto de rota deve ser passado para o callback.
     * @param string $alias Alias para a rota.
     * @return Route
     */
    public function get(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('GET ' . $pattern, $callback, $pass_route, $alias);
    }

    /**
     * Cria uma rota POST.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Callback associado.
     * @param bool $pass_route Indica se o objeto de rota deve ser passado para o callback.
     * @param string $alias Alias para a rota.
     * @return Route
     */
    public function post(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('POST ' . $pattern, $callback, $pass_route, $alias);
    }

    /**
     * Cria uma rota PUT.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Callback associado.
     * @param bool $pass_route Indica se o objeto de rota deve ser passado para o callback.
     * @param string $alias Alias para a rota.
     * @return Route
     */
    public function put(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('PUT ' . $pattern, $callback, $pass_route, $alias);
    }

    /**
     * Cria uma rota PATCH.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Callback associado.
     * @param bool $pass_route Indica se o objeto de rota deve ser passado para o callback.
     * @param string $alias Alias para a rota.
     * @return Route
     */
    public function patch(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('PATCH ' . $pattern, $callback, $pass_route, $alias);
    }

    /**
     * Cria uma rota DELETE.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Callback associado.
     * @param bool $pass_route Indica se o objeto de rota deve ser passado para o callback.
     * @param string $alias Alias para a rota.
     * @return Route
     */
    public function delete(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('DELETE ' . $pattern, $callback, $pass_route, $alias);
    }

    /**
     * Agrupa rotas sob um prefixo comum.
     *
     * @param string $groupPrefix Prefixo do grupo.
     * @param callable $callback Função que define as rotas do grupo.
     * @param array<int, callable|object> $groupMiddlewares Middlewares aplicados ao grupo.
     */
    public function group(string $groupPrefix, callable $callback, array $groupMiddlewares = []): void
    {
        $oldGroupPrefix = $this->groupPrefix;
        $oldGroupMiddlewares = $this->groupMiddlewares;

        $this->groupPrefix .= $groupPrefix;
        $this->groupMiddlewares = array_merge($this->groupMiddlewares, $groupMiddlewares);

        $callback($this);

        $this->groupPrefix = $oldGroupPrefix;
        $this->groupMiddlewares = $oldGroupMiddlewares;
    }

    /**
     * Processa a rota para a requisição atual.
     *
     * @param Request $request Requisição HTTP.
     * @return false|Route Rota correspondente ou `false` se não houver correspondência.
     */
    public function route(Request $request)
    {
        while ($route = $this->current()) {
            if ($route->matchUrl($request->url, $this->case_sensitive) && $route->matchMethod($request->method)) {
                $this->executedRoute = $route;
                return $route;
            }
            $this->next();
        }

        return false;
    }

    /**
     * Obtém a URL associada a um alias de rota.
     *
     * @param string $alias Alias da rota.
     * @param array<string, mixed> $params Parâmetros para a rota.
     * @return string URL correspondente ao alias.
     * @throws Exception Se o alias não for encontrado.
     */
    public function getUrlByAlias(string $alias, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->matchAlias($alias)) {
                return $route->hydrateUrl($params);
            }
        }

        throw new Exception("No route found with alias: '{$alias}'.");
    }

    /**
     * Redefine o índice de rotas para o início.
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * Avança para a próxima rota.
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * Retorna a rota atual.
     *
     * @return false|Route Rota atual ou `false` se não existir.
     */
    public function current()
    {
        return $this->routes[$this->index] ?? false;
    }
}
