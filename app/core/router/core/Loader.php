<?php

declare(strict_types=1);

namespace App\core\router\core;

use Closure;
use Exception;

/**
 * A classe Loader é responsável por carregar objetos.
 * Ela mantém uma lista de instâncias de classes reutilizáveis
 * e pode gerar novas instâncias de classes com parâmetros de inicialização personalizados.
 * Também suporta carregamento automático de classes.
 */
class Loader
{
    /**
     * Classes registradas.
     *
     * @var array<string, array{class-string|Closure(): object, array<int, mixed>, ?callable}>
     */
    protected array $classes = [];

    /**
     * Controla se o carregamento no estilo da versão 2 é permitido.
     */
    protected static bool $v2ClassLoading = true;

    /**
     * Instâncias de classes.
     *
     * @var array<string, object>
     */
    protected array $instances = [];

    /**
     * Diretórios de autoload.
     *
     * @var array<int, string>
     */
    protected static array $dirs = [];

    /**
     * Registra uma classe.
     *
     * @param string $name Nome para o registro.
     * @param class-string<T>|Closure(): T $class Nome da classe ou callback para criar a instância.
     * @param array<int, mixed> $params Parâmetros de inicialização da classe.
     * @param ?Closure(T $instance): void $callback Callback a ser executado após a criação da instância.
     *
     * @template T of object
     */
    public function register(string $name, $class, array $params = [], ?callable $callback = null): void
    {
        unset($this->instances[$name]); // Remove instância existente, se houver.
        $this->classes[$name] = [$class, $params, $callback];
    }

    /**
     * Remove o registro de uma classe.
     *
     * @param string $name Nome do registro.
     */
    public function unregister(string $name): void
    {
        unset($this->classes[$name]);
    }

    /**
     * Carrega uma classe registrada.
     *
     * @param string $name Nome da classe.
     * @param bool $shared Define se a instância será compartilhada.
     *
     * @throws Exception Se a classe não puder ser instanciada.
     * @return ?object Instância da classe.
     */
    public function load(string $name, bool $shared = true): ?object
    {
        $obj = null;

        if (isset($this->classes[$name])) {
            [0 => $class, 1 => $params, 2 => $callback] = $this->classes[$name];
            $exists = isset($this->instances[$name]);

            if ($shared) {
                $obj = $exists ? $this->getInstance($name) : $this->newInstance($class, $params);
                if (!$exists) {
                    $this->instances[$name] = $obj;
                }
            } else {
                $obj = $this->newInstance($class, $params);
            }

            if ($callback && (!$shared || !$exists)) {
                $ref = [&$obj];
                \call_user_func_array($callback, $ref);
            }
        }

        return $obj;
    }

    /**
     * Retorna uma instância já criada de uma classe.
     *
     * @param string $name Nome da instância.
     * @return ?object Instância da classe.
     */
    public function getInstance(string $name): ?object
    {
        return $this->instances[$name] ?? null;
    }

    /**
     * Cria uma nova instância de uma classe.
     *
     * @param class-string<T>|Closure(): class-string<T> $class Nome da classe ou callback para criar a instância.
     * @param array<int, string> $params Parâmetros para inicialização.
     *
     * @template T of object
     * @throws Exception Se a classe não puder ser instanciada.
     * @return T Nova instância da classe.
     */
    public function newInstance($class, array $params = [])
    {
        if (\is_callable($class)) {
            return \call_user_func_array($class, $params);
        }

        return new $class(...$params);
    }

    /**
     * Obtém os detalhes de uma classe registrada.
     *
     * @param string $name Nome do registro.
     * @return mixed Detalhes da classe registrada ou null.
     */
    public function get(string $name)
    {
        return $this->classes[$name] ?? null;
    }

    /**
     * Reseta o estado do objeto para o inicial.
     */
    public function reset(): void
    {
        $this->classes = [];
        $this->instances = [];
    }

    // Métodos para Autoloading

    /**
     * Liga/desliga o autoload.
     *
     * @param bool $enabled Ativa/desativa o autoload.
     * @param string|iterable<int, string> $dirs Diretórios para autoload.
     */
    public static function autoload(bool $enabled = true, $dirs = []): void
    {
        if ($enabled) {
            spl_autoload_register([__CLASS__, 'loadClass']);
        } else {
            spl_autoload_unregister([__CLASS__, 'loadClass']);
        }

        if (!empty($dirs)) {
            self::addDirectory($dirs);
        }
    }

    /**
     * Realiza o autoload de uma classe.
     *
     * @param string $class Nome da classe.
     */
    public static function loadClass(string $class): void
    {
        $replace_chars = self::$v2ClassLoading ? ['\\', '_'] : ['\\'];
        $classFile = str_replace($replace_chars, '/', $class) . '.php';

        foreach (self::$dirs as $dir) {
            $filePath = "$dir/$classFile";

            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }

    /**
     * Adiciona um diretório para autoload.
     *
     * @param string|iterable<int, string> $dir Diretório(s) para autoload.
     */
    public static function addDirectory($dir): void
    {
        if (\is_array($dir) || \is_object($dir)) {
            foreach ($dir as $value) {
                self::addDirectory($value);
            }
        } elseif (\is_string($dir) && !\in_array($dir, self::$dirs, true)) {
            self::$dirs[] = $dir;
        }
    }

    /**
     * Define o valor para o carregamento de classes no estilo V2.
     *
     * @param bool $value Valor a ser definido.
     */
    public static function setV2ClassLoading(bool $value): void
    {
        self::$v2ClassLoading = $value;
    }
}
