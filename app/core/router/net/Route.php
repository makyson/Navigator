<?php

declare(strict_types=1);

namespace App\core\router\net;

/**
 * Classe Route
 *
 * Representa uma rota no sistema de roteamento.
 * É responsável por verificar correspondências entre URLs solicitadas e padrões de rota,
 * além de armazenar informações sobre a rota, como parâmetros, middlewares, alias, e mais.
 */
class Route
{
    /**
     * Padrão da URL associado à rota.
     */
    public string $pattern;

    /**
     * Função de callback associada à rota.
     *
     * @var mixed
     */
    public $callback;

    /**
     * Métodos HTTP permitidos para a rota.
     *
     * @var array<int, string>
     */
    public array $methods = [];

    /**
     * Parâmetros extraídos da URL correspondente à rota.
     *
     * @var array<int, ?string>
     */
    public array $params = [];

    /**
     * Expressão regular gerada para correspondência de URLs.
     */
    public ?string $regex = null;

    /**
     * Conteúdo do "splat" (partes da URL não nomeadas).
     */
    public string $splat = '';

    /**
     * Define se a própria instância de `Route` deve ser passada ao callback.
     */
    public bool $pass = false;

    /**
     * Alias da rota, utilizado como identificador amigável.
     */
    public string $alias = '';

    /**
     * Middlewares associados à rota.
     *
     * @var array<int, callable|object|string>
     */
    public array $middleware = [];

    /**
     * Indica se a resposta da rota será transmitida (streamed).
     */
    public bool $is_streamed = false;

    /**
     * Cabeçalhos a serem enviados antes do início da transmissão (stream).
     *
     * @var array<string, mixed>
     */
    public array $streamed_headers = [];

    /**
     * Construtor.
     *
     * Inicializa uma rota com o padrão da URL, callback, métodos HTTP e outras configurações.
     *
     * @param string $pattern Padrão da URL.
     * @param callable|string $callback Função de callback.
     * @param array<int, string> $methods Métodos HTTP permitidos.
     * @param bool $pass Define se a própria instância deve ser passada ao callback.
     * @param string $alias Alias da rota (opcional).
     */
    public function __construct(string $pattern, $callback, array $methods, bool $pass, string $alias = '')
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->methods = $methods;
        $this->pass = $pass;
        $this->alias = $alias;
    }

    /**
     * Verifica se a URL solicitada corresponde ao padrão da rota.
     *
     * @param string $url URL solicitada.
     * @param bool $case_sensitive Define se a correspondência é case-sensitive.
     *
     * @return bool `true` se houver correspondência, caso contrário `false`.
     */
    public function matchUrl(string $url, bool $case_sensitive = false): bool
    {
        if ($this->pattern === '*' || $this->pattern === $url) {
            return true;
        }

        // Construção de expressão regular e validação
        $regex = $this->buildRegex($url);
        if (!preg_match('#^' . $regex . '(?:\?[\s\S]*)?$#' . ($case_sensitive ? '' : 'i'), $url, $matches)) {
            return false;
        }

        // Extração de parâmetros da URL
        foreach (array_keys($this->params) as $key) {
            $this->params[$key] = $matches[$key] ?? null;
        }

        $this->regex = $regex;

        return true;
    }

    /**
     * Verifica se o método HTTP da requisição é permitido para a rota.
     *
     * @param string $method Método HTTP da requisição.
     *
     * @return bool `true` se o método é permitido, caso contrário `false`.
     */
    public function matchMethod(string $method): bool
    {
        return in_array($method, $this->methods, true) || in_array('*', $this->methods, true);
    }

    /**
     * Verifica se o alias fornecido corresponde ao alias da rota.
     *
     * @param string $alias Alias fornecido.
     *
     * @return bool `true` se houver correspondência, caso contrário `false`.
     */
    public function matchAlias(string $alias): bool
    {
        return $this->alias === $alias;
    }

    /**
     * Preenche a URL da rota com os parâmetros fornecidos.
     *
     * @param array<string, mixed> $params Parâmetros para preencher na URL.
     *
     * @return string URL preenchida.
     */
    public function hydrateUrl(array $params = []): string
    {
        $url = preg_replace_callback(
            '/@([\w]+)/',
            function ($matches) use ($params) {
                return $params[$matches[1]] ?? '';
            },
            $this->pattern
        );

        return rtrim($url, '/');
    }

    /**
     * Define o alias da rota.
     *
     * @param string $alias Alias da rota.
     *
     * @return self
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Adiciona middleware(s) à rota.
     *
     * @param array<int, callable|string>|callable|string $middleware Middleware(s) a serem adicionados.
     *
     * @return self
     */
    public function addMiddleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    /**
     * Habilita o streaming de resposta para a rota.
     *
     * @return self
     */
    public function stream(): self
    {
        $this->is_streamed = true;
        return $this;
    }

    /**
     * Configura o streaming de resposta com cabeçalhos.
     *
     * @param array<string, mixed> $headers Cabeçalhos a serem enviados antes do streaming.
     *
     * @return self
     */
    public function streamWithHeaders(array $headers): self
    {
        $this->is_streamed = true;
        $this->streamed_headers = $headers;
        return $this;
    }

    /**
     * Constrói a expressão regular para validação de URL.
     *
     * @param string $url URL solicitada.
     *
     * @return string Expressão regular construída.
     */
    private function buildRegex(string $url): string
    {
        $pattern = preg_replace(
            '/@([\w]+)/',
            '(?P<$1>[^/]+)',
            str_replace(['/*', '/'], ['/.*', '\/'], $this->pattern)
        );

        return '^' . rtrim($pattern, '/') . '/?$';
    }
}
