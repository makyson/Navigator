<?php

declare(strict_types=1);

namespace App\core\router\net;

use Exception;


/**
     * A classe Response representa uma resposta HTTP.
     * Contém cabeçalhos de resposta, código de status HTTP e o corpo da resposta.
     */
class Response
{
    /**
     * Se a resposta deve incluir o cabeçalho Content-Length.
     */
    public bool $content_length = true;

    /**
     * Controle de buffer de saída (manter para compatibilidade com versões anteriores).
     * Será removido na versão 4.
     */
    public bool $v2_output_buffering = false;

    /**
     * Códigos de status HTTP e suas descrições.
     */
    public static array $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',

        226 => 'IM Used',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',

        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',

        426 => 'Upgrade Required',

        428 => 'Precondition Required',
        429 => 'Too Many Requests',

        431 => 'Request Header Fields Too Large',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',

        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Código de status da resposta HTTP.
     */
    protected int $status = 200;

    /**
     * Cabeçalhos da resposta HTTP.
     */
    protected array $headers = [];

    /**
     * Corpo da resposta HTTP.
     */
    protected string $body = '';

    /**
     * Indica se a resposta já foi enviada.
     */
    protected bool $sent = false;

    /**
     * Callbacks para processar o corpo da resposta antes de enviar.
     */
    protected array $responseBodyCallbacks = [];

    /**
     * Define o código de status HTTP da resposta.
     *
     * @param ?int $code Código de status HTTP.
     * @return int|self Retorna o código atual ou a instância.
     * @throws Exception Se o código de status for inválido.
     */
    public function status(?int $code = null)
    {
        if ($code === null) {
            return $this->status;
        }

        if (\array_key_exists($code, self::$codes)) {
            $this->status = $code;
        } else {
            throw new Exception('Invalid status code.');
        }

        return $this;
    }

    /**
     * Adiciona um cabeçalho à resposta.
     *
     * @param array|string $name Nome do cabeçalho ou um array de cabeçalhos.
     * @param ?string $value Valor do cabeçalho (se aplicável).
     * @return self
     */
    public function header($name, ?string $value = null): self
    {
        if (\is_array($name)) {
            foreach ($name as $k => $v) {
                $this->headers[$k] = $v;
            }
        } else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Obtém um cabeçalho específico da resposta.
     *
     * @param string $name Nome do cabeçalho.
     * @return string|null Valor do cabeçalho ou null se não existir.
     */
    public function getHeader(string $name): ?string
    {
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        return $headers[strtolower($name)] ?? null;
    }

    /**
     * Escreve conteúdo no corpo da resposta.
     *
     * @param string $str Conteúdo a ser escrito.
     * @param bool $overwrite Se o corpo existente deve ser sobrescrito.
     * @return self
     */
    public function write(string $str, bool $overwrite = false): self
    {
        if ($overwrite) {
            $this->clearBody();
        }
        $this->body .= $str;

        return $this;
    }

    /**
     * Limpa o corpo da resposta.
     *
     * @return self
     */
    public function clearBody(): self
    {
        $this->body = '';
        return $this;
    }

    /**
     * Define cabeçalhos para desativar o cache.
     *
     * @param int|string|false $expires Tempo de expiração do cache.
     * @return self
     */
    public function cache($expires): self
    {
        if ($expires === false || $expires === 0) {
            $this->headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
            $this->headers['Pragma'] = 'no-cache';
        } else {
            $expires = \is_int($expires) ? $expires : strtotime($expires);
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age=' . ($expires - time());
        }

        return $this;
    }

    /**
     * Envia os cabeçalhos HTTP.
     *
     * @return self
     */
    public function sendHeaders(): self
    {
        if (strpos(\PHP_SAPI, 'cgi') !== false) {
            $this->setRealHeader(
                sprintf('Status: %d %s', $this->status, self::$codes[$this->status]),
                true
            );
        } else {
            $this->setRealHeader(
                sprintf('%s %d %s', $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1', $this->status, self::$codes[$this->status]),
                true,
                $this->status
            );
        }

        foreach ($this->headers as $field => $value) {
            if (\is_array($value)) {
                foreach ($value as $v) {
                    $this->setRealHeader($field . ': ' . $v, false);
                }
            } else {
                $this->setRealHeader($field . ': ' . $value);
            }
        }

        return $this;
    }

    /**
     * Envia a resposta HTTP.
     */
    public function send(): void
    {
        if (!$this->headersSent()) {
            $this->sendHeaders();
        }
        echo $this->body;
        $this->sent = true;
    }

    /**
     * Adiciona um callback para processar o corpo da resposta.
     *
     * @param callable $callback Callback para processar o corpo.
     */
    public function addResponseBodyCallback(callable $callback): void
    {
        $this->responseBodyCallbacks[] = $callback;
    }

    /**
     * Processa os callbacks do corpo da resposta.
     */
    protected function processResponseCallbacks(): void
    {
        foreach ($this->responseBodyCallbacks as $callback) {
            $this->body = $callback($this->body);
        }
    }

    /**
     * Baixa um arquivo para o cliente.
     *
     * @param string $filePath Caminho do arquivo.
     */
    public function downloadFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("$filePath cannot be found.");
        }

        $this->send();
        $this->setRealHeader('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        $this->setRealHeader('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Define um cabeçalho real (função `header()`).
     *
     * @param string $header_string String do cabeçalho.
     * @param bool $replace Se deve substituir um cabeçalho existente.
     * @param int $response_code Código de status.
     * @return self
     */
    public function setRealHeader(string $header_string, bool $replace = true, int $response_code = 0): self
    {
        header($header_string, $replace, $response_code);
        return $this;
    }






    /**
     * Alias of Response->header(). Adds a header to the response.
     *
     * @param array<string, int|string>|string $name  Header name or array of names and values
     * @param ?string  $value Header value
     *
     * @return $this
     */
    public function setHeader($name, ?string $value): self
    {
        return $this->header($name, $value);
    }

    /**
     * Returns the headers from the response.
     *
     * @return array<string, int|string|array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Alias for Response->headers(). Returns the headers from the response.
     *
     * @return array<string, int|string|array<int, string>>
     */
    public function getHeaders(): array
    {
        return $this->headers();
    }




    /**
     * Clears the response.
     *
     * @return $this Self reference
     */
    public function clear(): self
    {
        $this->status = 200;
        $this->headers = [];
        $this->clearBody();

        // This needs to clear the output buffer if it's on
        if ($this->v2_output_buffering === false && ob_get_length() > 0) {
            ob_clean();
        }

        return $this;
    }






    /**
     * Gets the content length.
     */
    public function getContentLength(): int
    {
        return \extension_loaded('mbstring') ?
            mb_strlen($this->body, 'latin1') :
            \strlen($this->body);
    }

    /**
     * Gets the response body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Gets whether response body was sent.
     */
    public function sent(): bool
    {
        return $this->sent;
    }

    /**
     * Marks the response as sent.
     */
    public function markAsSent(): void
    {
        $this->sent = true;
    }


    /**
     * Headers have been sent
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function headersSent(): bool
    {
        return headers_sent();
    }






}







