<?php

declare(strict_types=1);

namespace App\core\router\core;



use App\core\router\Engine;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Throwable;
use TypeError;


/**
     * A classe Dispatcher é responsável por despachar eventos.
     * Os eventos são aliases para métodos de classes ou funções.
     * Ela também permite anexar filtros que podem modificar os parâmetros de entrada
     * e/ou a saída do evento.
     */
class Dispatcher
{
    // Constantes para os tipos de filtros
    public const FILTER_BEFORE = 'before';
    public const FILTER_AFTER = 'after';

    /** Exceção de contêiner, caso seja lançada ao configurar o contêiner como callable. */
    protected ?Throwable $containerException = null;

    /** @var ?Engine Instância da Engine associada. */
    protected ?Engine $engine = null;

    /** @var array<string, callable> Eventos mapeados. */
    protected array $events = [];

    /**
     * Filtros aplicados a eventos.
     *
     * @var array<string, array<'before'|'after', array<int, callable>>>
     */
    protected array $filters = [];

    /**
     * Contêiner para injeção de dependências.
     *
     * @var null|ContainerInterface|(callable)
     */
    protected $containerHandler = null;

    /**
     * Configura o contêiner para injeção de dependências.
     *
     * @param ContainerInterface|callable $containerHandler Contêiner ou callable.
     * @throws InvalidArgumentException Se o contêiner não for válido.
     */
    public function setContainerHandler($containerHandler): void
    {
        $containerInterfaceNS = '\Psr\Container\ContainerInterface';

        if (is_a($containerHandler, $containerInterfaceNS) || is_callable($containerHandler)) {
            $this->containerHandler = $containerHandler;
            return;
        }

        throw new InvalidArgumentException(
            "\$containerHandler deve ser um callable ou uma instância de $containerInterfaceNS"
        );
    }

    /**
     * Configura a instância da Engine.
     *
     * @param Engine $engine Instância da Engine.
     */
    public function setEngine(Engine $engine): void
    {
        $this->engine = $engine;
    }

    /**
     * Executa um evento.
     *
     * @param string $name Nome do evento.
     * @param array<int, mixed> $params Parâmetros para o evento.
     * @return mixed Saída do evento.
     * @throws Exception Se o evento não for encontrado ou lançar uma exceção.
     */
    public function run(string $name, array $params = [])
    {
        $this->runPreFilters($name, $params); // Executa os filtros "before"
        $output = $this->runEvent($name, $params); // Executa o evento principal
        return $this->runPostFilters($name, $output); // Executa os filtros "after"
    }

    /**
     * Executa filtros "before" associados a um evento.
     *
     * @param string $eventName Nome do evento.
     * @param array<int, mixed> &$params Parâmetros do evento.
     * @return $this
     */
    protected function runPreFilters(string $eventName, array &$params): self
    {
        if (!empty($this->filters[$eventName][self::FILTER_BEFORE])) {
            $this->filter($this->filters[$eventName][self::FILTER_BEFORE], $params, $output);
        }
        return $this;
    }

    /**
     * Executa o evento principal.
     *
     * @param string $eventName Nome do evento.
     * @param array<int, mixed> &$params Parâmetros do evento.
     * @return mixed Resultado do evento.
     * @throws Exception Se o evento não for encontrado.
     */
    protected function runEvent(string $eventName, array &$params)
    {
        $requestedMethod = $this->get($eventName);
        if ($requestedMethod === null) {
            throw new Exception("Evento '$eventName' não encontrado.");
        }
        return $this->execute($requestedMethod, $params);
    }

    /**
     * Executa filtros "after" associados a um evento.
     *
     * @param string $eventName Nome do evento.
     * @param mixed &$output Saída do evento.
     * @return mixed Saída modificada pelo filtro.
     */
    protected function runPostFilters(string $eventName, &$output)
    {
        if (!empty($this->filters[$eventName][self::FILTER_AFTER])) {
            static $params = [];
            $this->filter($this->filters[$eventName][self::FILTER_AFTER], $params, $output);
        }
        return $output;
    }

    /**
     * Define um evento com seu callback.
     *
     * @param string $name Nome do evento.
     * @param callable $callback Função de callback.
     * @return $this
     */
    public function set(string $name, callable $callback): self
    {
        $this->events[$name] = $callback;
        return $this;
    }

    /**
     * Obtém o callback associado a um evento.
     *
     * @param string $name Nome do evento.
     * @return null|callable Callback associado ao evento.
     */
    public function get(string $name): ?callable
    {
        return $this->events[$name] ?? null;
    }

    /**
     * Adiciona um filtro a um evento.
     *
     * @param string $name Nome do evento.
     * @param 'before'|'after' $type Tipo do filtro.
     * @param callable $callback Callback do filtro.
     * @return $this
     */
    public function hook(string $name, string $type, callable $callback): self
    {
        $this->filters[$name][$type][] = $callback;
        return $this;
    }

    /**
     * Aplica uma cadeia de filtros.
     *
     * @param array<int, callable> $filters Cadeia de filtros.
     * @param array<int, mixed> &$params Parâmetros.
     * @param mixed &$output Saída.
     */
    public function filter(array $filters, array &$params, &$output): void
    {
        foreach ($filters as $callback) {
            $continue = $callback($params, $output);
            if ($continue === false) {
                break;
            }
        }
    }






    /**
     * Checks if an event has been set.
     *
     * @param string $name Event name.
     *
     * @return bool If event exists or doesn't exists.
     */
    public function has(string $name): bool
    {
        return isset($this->events[$name]);
    }

    /**
     * Clears an event. If no name is given, all events will be removed.
     *
     * @param ?string $name Event name.
     */
    public function clear(?string $name = null): void
    {
        if ($name !== null) {
            unset($this->events[$name]);
            unset($this->filters[$name]);

            return;
        }

        $this->reset();
    }


    /**
     * Executes a callback function.
     *
     * @param callable-string|(callable(): mixed)|array{class-string|object, string} $callback
     * Callback function.
     * @param array<int, mixed> $params Function parameters.
     *
     * @return mixed Function results.
     * @throws Exception If `$callback` also throws an `Exception`.
     */
    public function execute($callback, array &$params = [])
    {
        if (
            is_string($callback) === true
            && (strpos($callback, '->') !== false || strpos($callback, '::') !== false)
        ) {
            $callback = $this->parseStringClassAndMethod($callback);
        }

        return $this->invokeCallable($callback, $params);
    }

    /**
     * Parses a string into a class and method.
     *
     * @param string $classAndMethod Class and method
     *
     * @return array{0: class-string|object, 1: string} Class and method
     */
    public function parseStringClassAndMethod(string $classAndMethod): array
    {
        $classParts = explode('->', $classAndMethod);

        if (count($classParts) === 1) {
            $classParts = explode('::', $classParts[0]);
        }

        return $classParts;
    }

    /**
     * Calls a function.
     *
     * @param callable $func Name of function to call.
     * @param array<int, mixed> &$params Function parameters.
     *
     * @return mixed Function results.
     * @deprecated 3.7.0 Use invokeCallable instead
     */
    public function callFunction(callable $func, array &$params = [])
    {
        return $this->invokeCallable($func, $params);
    }

    /**
     * Invokes a method.
     *
     * @param array{0: class-string|object, 1: string} $func Class method.
     * @param array<int, mixed> &$params Class method parameters.
     *
     * @return mixed Function results.
     * @throws TypeError For nonexistent class name.
     * @deprecated 3.7.0 Use invokeCallable instead.
     */
    public function invokeMethod(array $func, array &$params = [])
    {
        return $this->invokeCallable($func, $params);
    }

    /**
     * Invokes a callable (anonymous function or Class->method).
     *
     * @param array{0: class-string|object, 1: string}|callable $func Class method.
     * @param array<int, mixed> &$params Class method parameters.
     *
     * @return mixed Function results.
     * @throws TypeError For nonexistent class name.
     * @throws InvalidArgumentException If the constructor requires parameters.
     * @version 3.7.0
     */
    public function invokeCallable($func, array &$params = [])
    {
        // If this is a directly callable function, call it
        if (is_array($func) === false) {
            $this->verifyValidFunction($func);

            return call_user_func_array($func, $params);
        }

        [$class, $method] = $func;

        $mustUseTheContainer = $this->mustUseContainer($class);

        if ($mustUseTheContainer === true) {
            $resolvedClass = $this->resolveContainerClass($class, $params);

            if ($resolvedClass) {
                $class = $resolvedClass;
            }
        }

        $this->verifyValidClassCallable($class, $method, $resolvedClass ?? null);

        // Class is a string, and method exists, create the object by hand and inject only the Engine
        if (is_string($class)) {
            $class = new $class($this->engine);
        }

        return call_user_func_array([$class, $method], $params);
    }

    /**
     * Handles invalid callback types.
     *
     * @param callable-string|(callable(): mixed)|array{0: class-string|object, 1: string} $callback
     * Callback function.
     *
     * @throws InvalidArgumentException If `$callback` is an invalid type.
     */
    protected function verifyValidFunction($callback): void
    {
        if (is_string($callback) && !function_exists($callback)) {
            throw new InvalidArgumentException('Invalid callback specified.');
        }
    }


    /**
     * Verifies if the provided class and method are valid callable.
     *
     * @param class-string|object $class The class name.
     * @param string $method The method name.
     * @param object|null $resolvedClass The resolved class.
     *
     * @throws Exception If the class or method is not found.
     */
    protected function verifyValidClassCallable($class, string $method, ?object $resolvedClass): void
    {
        $exception = null;

        // Final check to make sure it's actually a class and a method, or throw an error
        if (is_object($class) === false && class_exists($class) === false) {
            $exception = new Exception("Class '$class' not found. Is it being correctly autoloaded with navigator::path()?");

            // If this tried to resolve a class in a container and failed somehow, throw the exception
        } elseif (!$resolvedClass && $this->containerException !== null) {
            $exception = $this->containerException;

            // Class is there, but no method
        } elseif (is_object($class) === true && method_exists($class, $method) === false) {
            $classNamespace = get_class($class);
            $exception = new Exception("Class found, but method '$classNamespace::$method' not found.");
        }

        if ($exception !== null) {
            $this->fixOutputBuffering();

            throw $exception;
        }
    }

    /**
     * Resolves the container class.
     *
     * @param class-string $class Class name.
     * @param array<int, mixed> &$params Class constructor parameters.
     *
     * @return ?object Class object.
     */
    public function resolveContainerClass(string $class, array &$params)
    {
        // PSR-11
        if (
            is_a($this->containerHandler, '\Psr\Container\ContainerInterface')
            && $this->containerHandler->has($class)
        ) {
            return $this->containerHandler->get($class);
        }

        // Just a callable where you configure the behavior (Dice, PHP-DI, etc.)
        if (is_callable($this->containerHandler)) {
            /* This is to catch all the error that could be thrown by whatever
            container you are using */
            try {
                return ($this->containerHandler)($class, $params);

                // could not resolve a class for some reason
            } catch (Exception $exception) {
                // If the container throws an exception, we need to catch it
                // and store it somewhere. If we just let it throw itself, it
                // doesn't properly close the output buffers and can cause other
                // issues.
                // This is thrown in the verifyValidClassCallable method.
                $this->containerException = $exception;
            }
        }

        return null;
    }

    /**
     * Checks to see if a container should be used or not.
     *
     * @param string|object $class the class to verify
     *
     * @return boolean
     */
    public function mustUseContainer($class): bool
    {
        return $this->containerHandler !== null && (
            (is_object($class) === true && strpos(get_class($class), 'navigator\\') === false)
            || is_string($class)
        );
    }

    /** Because this could throw an exception in the middle of an output buffer, */
    protected function fixOutputBuffering(): void
    {
        // Cause PHPUnit has 1 level of output buffering by default
        if (ob_get_level() > (getenv('PHPUNIT_TEST') ? 1 : 0)) {
            ob_end_clean();
        }
    }

    /**
     * Resets the object to the initial state.
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->events = [];
        $this->filters = [];

        return $this;
    }
}
