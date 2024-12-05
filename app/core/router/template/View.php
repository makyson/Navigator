<?php

declare(strict_types=1);

namespace App\core\router\template;

/**
 * Classe View
 *
 * Gerencia templates para renderização de páginas, manipulando variáveis e arquivos de template.
 * Permite configurar caminhos, extensões e preservar variáveis entre chamadas.
 */
class View
{
    /**
     * Caminho para o diretório de templates.
     */
    public string $path;

    /**
     * Extensão dos arquivos de template.
     */
    public string $extension = '.php';

    /**
     * Indica se as variáveis do template devem ser preservadas entre chamadas.
     */
    public bool $preserveVars = true;

    /**
     * Variáveis do template.
     *
     * @var array<string, mixed>
     */
    protected array $vars = [];

    /**
     * Arquivo de template.
     */
    private string $template;

    /**
     * Construtor.
     *
     * Inicializa a classe com o caminho para o diretório de templates.
     *
     * @param string $path Caminho para o diretório de templates.
     */
    public function __construct(string $path = '.')
    {
        $this->path = $path;
    }

    /**
     * Obtém uma variável do template.
     *
     * @param string $key Nome da variável.
     * @return mixed Valor da variável ou `null` se não existir.
     */
    public function get(string $key)
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Define uma ou mais variáveis para o template.
     *
     * @param string|iterable<string, mixed> $key Nome da variável ou conjunto de variáveis.
     * @param mixed $value Valor da variável (ignorado se `$key` for iterável).
     * @return self
     */
    public function set($key, $value = null): self
    {
        if (\is_iterable($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            $this->vars[$key] = $value;
        }

        return $this;
    }

    /**
     * Verifica se uma variável do template está definida.
     *
     * @param string $key Nome da variável.
     * @return bool `true` se a variável existir, caso contrário `false`.
     */
    public function has(string $key): bool
    {
        return isset($this->vars[$key]);
    }

    /**
     * Remove uma variável do template ou limpa todas as variáveis.
     *
     * @param ?string $key Nome da variável (limpa todas se `null`).
     * @return self
     */
    public function clear(?string $key = null): self
    {
        if ($key === null) {
            $this->vars = [];
        } else {
            unset($this->vars[$key]);
        }

        return $this;
    }

    /**
     * Renderiza um template.
     *
     * @param string $file Nome do arquivo de template.
     * @param ?array<string, mixed> $data Dados para o template.
     * @throws \Exception Se o arquivo de template não for encontrado.
     */
    public function render(string $file, ?array $data = null): void
    {
        $this->template = $this->getTemplate($file);

        if (!\file_exists($this->template)) {
            $normalized_path = self::normalizePath($this->template);
            throw new \Exception("Template file not found: {$normalized_path}.");
        }

        \extract($this->vars);

        if (\is_array($data)) {
            \extract($data);

            if ($this->preserveVars) {
                $this->vars = \array_merge($this->vars, $data);
            }
        }

        include $this->template;
    }

    /**
     * Obtém a saída de um template como string.
     *
     * @param string $file Nome do arquivo de template.
     * @param ?array<string, mixed> $data Dados para o template.
     * @return string Saída renderizada.
     */
    public function fetch(string $file, ?array $data = null): string
    {
        \ob_start();
        $this->render($file, $data);
        return \ob_get_clean();
    }

    /**
     * Verifica se um arquivo de template existe.
     *
     * @param string $file Nome do arquivo de template.
     * @return bool `true` se o arquivo existir, caso contrário `false`.
     */
    public function exists(string $file): bool
    {
        return \file_exists($this->getTemplate($file));
    }

    /**
     * Obtém o caminho completo para um arquivo de template.
     *
     * @param string $file Nome do arquivo de template.
     * @return string Caminho completo do arquivo.
     */
    public function getTemplate(string $file): string
    {
        $ext = $this->extension;

        if (!empty($ext) && (\substr($file, -1 * \strlen($ext)) !== $ext)) {
            $file .= $ext;
        }

        $is_windows = \strtoupper(\substr(PHP_OS, 0, 3)) === 'WIN';

        if ((\substr($file, 0, 1) === '/') || ($is_windows && \substr($file, 1, 1) === ':')) {
            return $file;
        }

        return $this->path . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Escapa e exibe uma string.
     *
     * @param string $str String a ser escapada.
     * @return string String escapada.
     */
    public function e(string $str): string
    {
        $value = \htmlentities($str);
        echo $value;
        return $value;
    }

    /**
     * Normaliza um caminho para o formato do sistema operacional atual.
     *
     * @param string $path Caminho original.
     * @param string $separator Separador desejado (padrão: `DIRECTORY_SEPARATOR`).
     * @return string Caminho normalizado.
     */
    protected static function normalizePath(string $path, string $separator = DIRECTORY_SEPARATOR): string
    {
        return \str_replace(['\\', '/'], $separator, $path);
    }
}
