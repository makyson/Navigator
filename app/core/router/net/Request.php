<?php

declare(strict_types=1);

namespace App\core\router\net;

use App\core\router\util\Collection;

/**
 * A classe Request representa uma requisição HTTP.
 * Ela unifica dados de várias superglobais ($_GET, $_POST, $_COOKIE, $_FILES)
 * em uma única interface para fácil acesso e manipulação.
 */
class Request
{
    // Propriedades que representam os dados da requisição HTTP
    public string $url;          // URL sendo requisitada
    public string $base;         // Subdiretório base da URL
    public string $method;       // Método HTTP (GET, POST, PUT, DELETE)
    public string $referrer;     // URL de referência (referrer)
    public string $ip;           // Endereço IP do cliente
    public bool $ajax;           // Indica se a requisição é AJAX
    public string $scheme;       // Protocolo (http, https)
    public string $user_agent;   // Informações sobre o navegador do cliente
    public string $type;         // Tipo de conteúdo (Content-Type)
    public int $length;          // Comprimento do conteúdo (Content-Length)
    public Collection $query;    // Parâmetros da query string ($_GET)
    public Collection $data;     // Dados enviados ($_POST)
    public Collection $cookies;  // Cookies ($_COOKIE)
    public Collection $files;    // Arquivos enviados ($_FILES)
    public bool $secure;         // Indica se a conexão é segura
    public string $accept;       // Cabeçalho Accept da requisição
    public string $proxy_ip;     // IP do cliente através de proxy
    public string $host;         // Nome do host HTTP
    private string $stream_path = 'php://input'; // Caminho do stream de entrada
    public string $body = '';    // Corpo bruto da requisição

    /**
     * Construtor.
     *
     * @param array<string, mixed> $config Configurações da requisição.
     */
    public function __construct(array $config = [])
    {
        // Inicializa propriedades padrão se nenhuma configuração for fornecida.
        if (empty($config)) {
            $config = [
                'url'        => str_replace('@', '%40', self::getVar('REQUEST_URI', '/')),
                'base'       => str_replace(['\\', ' '], ['/', '%20'], \dirname(self::getVar('SCRIPT_NAME'))),
                'method'     => self::getMethod(),
                'referrer'   => self::getVar('HTTP_REFERER'),
                'ip'         => self::getVar('REMOTE_ADDR'),
                'ajax'       => self::getVar('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest',
                'scheme'     => self::getScheme(),
                'user_agent' => self::getVar('HTTP_USER_AGENT'),
                'type'       => self::getVar('CONTENT_TYPE'),
                'length'     => intval(self::getVar('CONTENT_LENGTH', 0)),
                'query'      => new Collection($_GET),
                'data'       => new Collection($_POST),
                'cookies'    => new Collection($_COOKIE),
                'files'      => new Collection($_FILES),
                'secure'     => self::getScheme() === 'https',
                'accept'     => self::getVar('HTTP_ACCEPT'),
                'proxy_ip'   => self::getProxyIpAddress(),
                'host'       => self::getVar('HTTP_HOST'),
            ];
        }

        $this->init($config);
    }

    /**
     * Inicializa as propriedades da requisição.
     *
     * @param array<string, mixed> $properties Propriedades para inicializar.
     * @return self
     */
    public function init(array $properties = []): self
    {
        foreach ($properties as $name => $value) {
            $this->{$name} = $value;
        }

        // Ajusta a URL removendo o prefixo base se necessário.
        if ($this->base !== '/' && strpos($this->url, $this->base) === 0) {
            $this->url = substr($this->url, \strlen($this->base));
        }

        // Garante que a URL padrão seja '/' se estiver vazia.
        if (empty($this->url)) {
            $this->url = '/';
        } else {
            $_GET = array_merge($_GET, self::parseQuery($this->url));
            $this->query->setData($_GET);
        }

        // Processa JSON no corpo da requisição.
        if (strpos($this->type, 'application/json') === 0) {
            $body = $this->getBody();
            if ($body !== '') {
                $data = json_decode($body, true);
                if (is_array($data)) {
                    $this->data->setData($data);
                }
            }
        }

        return $this;
    }




    /**
     * Parse query parameters from a URL.
     *
     * @param string $url URL string
     *
     * @return array<string, int|string|array<int|string, int|string>>
     */
    public static function parseQuery(string $url): array
    {
        $params = [];

        $args = parse_url($url);
        if (isset($args['query']) === true) {
            parse_str($args['query'], $params);
        }

        return $params;
    }


    /**
     * Gets the URL Scheme
     *
     * @return string 'http'|'https'
     */
    public static function getScheme(): string
    {
        if (
            (isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === 'on')
            ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) === true && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ||
            (isset($_SERVER['HTTP_FRONT_END_HTTPS']) === true && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
            ||
            (isset($_SERVER['REQUEST_SCHEME']) === true && $_SERVER['REQUEST_SCHEME'] === 'https')
        ) {
            return 'https';
        }

        return 'http';
    }


    /**
     * Obtém o corpo bruto da requisição.
     *
     * @return string Corpo da requisição.
     */
    public function getBody(): string
    {
        if ($this->body !== '') {
            return $this->body;
        }

        $method = $this->method ?? self::getMethod();
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $this->body = file_get_contents($this->stream_path);
        }

        return $this->body;
    }

    /**
     * Obtém o método HTTP da requisição.
     *
     * @return string Método HTTP.
     */
    public static function getMethod(): string
    {
        $method = self::getVar('REQUEST_METHOD', 'GET');

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif (isset($_REQUEST['_method'])) {
            $method = $_REQUEST['_method'];
        }

        return strtoupper($method);
    }

    /**
     * Obtém o endereço IP real do cliente (considerando proxies).
     *
     * @return string Endereço IP.
     */
    public static function getProxyIpAddress(): string
    {
        $forwardedHeaders = [
            'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
        ];

        foreach ($forwardedHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                foreach (explode(',', $_SERVER[$header]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return self::getVar('REMOTE_ADDR', '');
    }

    /**
     * Obtém uma variável do $_SERVER com valor padrão.
     *
     * @param string $var Nome da variável.
     * @param mixed $default Valor padrão.
     * @return mixed Valor da variável.
     */
    public static function getVar(string $var, $default = '')
    {
        return $_SERVER[$var] ?? $default;
    }

    // Outros métodos omitidos por brevidade (mas podem ser documentados de forma semelhante).
}
