<?php

declare(strict_types=1);

namespace App\core\router;


require "app/core/router/net/Request.php";
require "app/core/router/net/Response.php";
require "app/core/router/net/Route.php";
require "app/core/router/net/Router.php";
require "app/core/router/template/View.php";

use Exception;


/**
 *   Classe Navigator
 *
 *   Representação estática do framework, servindo como uma interface para os principais
 *   componentes e funcionalidades da aplicação.
 *
 *   ### Principais Funções
 *
 *  - Gerenciamento de rotas (mapeamento, agrupamento, métodos RESTful).
 *  - Manipulação de requisições e respostas HTTP.
 *  - Registro e recuperação de variáveis e instâncias no contêiner.
 *  - Suporte para exibição de templates.
 *  - Manipulação de erros e respostas JSON.
 *  - Funcionalidades auxiliares para caching HTTP, redirecionamentos e downloads.
 *
 *
 *# Métodos principais
 * @method static void start() Inicia a estrutura.
 * @method static void path(string $path) Adiciona um caminho para carregamento automático de classes.
 * @method static void stop(?int $code = null) Interrompe a estrutura e envia uma resposta.
 * @method static void halt(int $code = 200, string $message = '', bool $actuallyExit = true)
 *Pare a estrutura com um código de status e uma mensagem opcionais.
 * @method static void register(string $name, string $class, array $params = [], ?callable $callback = null)
 *Registra uma classe em um método de estrutura.
 *@method static void unregister(string $methodName)
 *Cancela o registro de uma classe em um método de estrutura.
 * @method static void registerContainerHandler(callable|object $containerHandler) Registra um manipulador de contêiner.
 *
 *#Roteamento
 * @method static Route route(string $pattern, callable|string $callback, bool $pass_route = false, string $alias = '')
 *Mapeia um padrão de URL para um retorno de chamada com todos os métodos aplicáveis.
 * @method static void group(string $pattern, callable $callback, callable[] $group_middlewares = [])
 *Agrupa um conjunto de rotas sob um prefixo comum.
 * @method static Route post(string $pattern, callable|string $callback, bool $pass_route = false, string $alias = '')
 *Encaminha uma URL POST para uma função de retorno de chamada.
 * @method static Route put(string $pattern, callable|string $callback, bool $pass_route = false, string $alias = '')
 *Encaminha uma URL PUT para uma função de retorno de chamada.
 * @method static Route patch(string $pattern, callable|string $callback, bool $pass_route = false, string $alias = '')
 *Roteia uma URL PATCH para uma função de retorno de chamada.
 * @method static Route delete(string $pattern, callable|string $callback, bool $pass_route = false, string $alias = '')
 *Roteia uma URL DELETE para uma função de retorno de chamada.
 * @method static void resource(string $pattern, string $controllerClass, array $methods = [])
 *Adiciona rotas RESTful padronizadas para um controlador.
 * @method static Router router() Retorna a instância do roteador.
 * @method static string getUrl(string $alias, array $params = []) Obtém uma URL de um alias
 *
 * @method static void map(string $name, callable $callback) Cria um método de estrutura personalizado.
 *
 * @method static void before(string $nome, \Closure $callback)
 *Adiciona um filtro antes de um método framework.
 * @method static void after(string $nome, \Closure $callback)
 *Adiciona um filtro após um método de estrutura.
 *
 * @method static void set(string|iterable $key, mixed $value) Define uma variável.
 * @method static mixed get(?string $key) Obtém uma variável.
 * @method static bool has(string $key) Verifica se uma variável está definida.
 * @method static void clear(?string $key = null) Limpa uma variável.
 *
 *# Visualizações
 * @method static void render(string $file, ?array $data = null, ?string $key = null)
 *Renderiza um arquivo de modelo.
 * @method static View view() Retorna a instância de View.
 *
 *# Solicitação-Resposta
 * @method static Request request() Retorna a instância do Request.
 * @method static Response response() Retorna a instância de Response.
 * @method static void redirecionamento(string $url, int $code = 303) Redireciona para outro URL.
 * @method static void json(mixed $dados, int $code = 200, bool $encode = true, string $charset = "utf8", int $encodeOption = 0, int $encodeDepth = 512)
 *Envia uma resposta JSON.
 * @method static void jsonHalt(mixed $dados, int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0)
 *Envia uma resposta JSON e interrompe imediatamente a solicitação.
 * @method static void jsonp(mixed $dados, string $param = 'jsonp', int $code = 200, bool $encode = true, string $charset = "utf8", int $encodeOption = 0, int $encodeDepth = 512)
 *Envia uma resposta JSONP.
 * @method static void error(\Throwable $exception) Envia uma resposta HTTP 500.
 * @method static void notFound() Envia uma resposta HTTP 404.
 *
 *# métodos HTTP
 * @method static void etag(string $id, $type = 'strong') Executa cache HTTP ETag.
 * @method static void lastModified(int $time) Executa o último cache HTTP modificado.
 * @method static void download(string $filePath) Baixa um arquivo
 */








class Navigator
{
    /**
     * @var Engine Instância principal do framework.
     */
    private static Engine $engine;

    /**
     * @var bool Indica se o framework foi inicializado.
     */
    private static bool $initialized = false;

    /**
     * Construtor privado para impedir a instanciação.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }



    /**
     * Método privado para impedir a clonagem.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Manipula chamadas de métodos estáticos para redirecioná-las ao mecanismo principal da aplicação.
     *
     * @param string $name Nome do método chamado.
     * @param array<int, mixed> $params Parâmetros fornecidos ao método.
     *
     * @return mixed Resultado da chamada ao método do mecanismo.
     * @throws Exception Se o método não for encontrado no mecanismo.
     */
    public static function __callStatic(string $name, array $params)
    {
        return self::app()->{$name}(...$params);
    }

    /**
     * Recupera ou inicializa a instância principal do framework.
     *
     * Carrega a configuração do framework e cria a instância de `Engine` caso ainda não tenha sido inicializada.
     *
     * @return Engine Instância principal do framework.
     */
    public static function app(): Engine
    {
        if (!self::$initialized) {


            self::setEngine(new Engine());
            self::$initialized = true;
        }

        return self::$engine;
    }

    /**
     * Define a instância do mecanismo principal.
     *
     * @param Engine $engine Instância principal a ser configurada.
     */
    public static function setEngine(Engine $engine): void
    {
        self::$engine = $engine;
    }
}