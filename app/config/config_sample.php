<?php


namespace  App\config;
/**
 * Este arquivo configura o ambiente da aplicação, define configurações para o framework Navigator,
 * inicializa o depurador Tracy, e fornece uma estrutura para armazenamento de configurações
 * sensíveis, como credenciais de banco de dados.
 */

// Definição de separador de diretório para compatibilidade entre sistemas operacionais
$ds = DIRECTORY_SEPARATOR;

use App\core\tracyextensions\debug\tracy\TracyExtensionLoader;
use App\core\router\Navigator;
use Tracy\Debugger;


// Carregamento do autoload gerado pelo Composer
require __DIR__ . '/../../vendor/autoload.php';

/**
 * Configurações Básicas
 */

// Define o fuso horário padrão da aplicação
date_default_timezone_set('America/Sao_Paulo');

// Define o nível de relatório de erros (todos os erros serão reportados)
error_reporting(E_ALL);

// Define a codificação de caracteres para UTF-8, se a extensão mbstring estiver disponível
if (function_exists('mb_internal_encoding') === true) {
    mb_internal_encoding('UTF-8');
}

// Define a localidade para pt_BR.UTF-8, caso seja suportada pelo sistema
if (function_exists('setlocale') === true) {
    setlocale(LC_ALL, 'pt_BR.UTF-8');
}

/**
 * Configurações do Navigator
 */

// Verifica se a instância do Navigator já foi criada, se não, inicializa
if (empty($app)) {
    $app = Navigator::app();
}

// Configuração de autoload para as classes na pasta `app`
$app->path(__DIR__ . $ds . '..' . $ds . 'app');

// Define a URL base da aplicação (ajuste se necessário)
$app->set('navigator.base_url', '/');

// Define se as rotas devem diferenciar maiúsculas e minúsculas
$app->set('navigator.case_sensitive', false);

// Define se o Navigator deve registrar logs de erro
$app->set('navigator.log_errors', true);

// Define se o Navigator deve gerenciar erros
$app->set('navigator.handle_errors', true);

// Define o caminho para os arquivos de views
$app->set('navigator.views.path', __DIR__ . $ds . 'app' . $ds . 'views');

// Define a extensão padrão para os arquivos de views
$app->set('navigator.views.extension', '.php');

// Define se o cabeçalho de comprimento de conteúdo (Content-Length) deve ser enviado
$app->set('navigator.content_length', true);

/**
 * Configuração do Debugger Tracy
 */

// Ativa o depurador Tracy no modo de desenvolvimento
Debugger::enable(Debugger::Development);

// Define o diretório onde os logs do Tracy serão armazenados
Debugger::$logDirectory = __DIR__ . $ds . 'log';

// Ativa o modo estrito, exibindo todos os erros
Debugger::$strictMode = true;

// Se a barra do Tracy estiver ativada e o PHP não estiver rodando em CLI, ajusta o Content-Length
if (Debugger::$showBar && php_sapi_name() !== 'cli') {
    $app->set('navigator.content_length', true);
    try {
        (new TracyExtensionLoader($app));
    } catch (Exception $e) {

    }
}

/**
 * Configuração de Banco de Dados
 *
 * Este é o local onde você armazena informações sensíveis, como credenciais de banco de dados.
 * Esta configuração será retornada no final do arquivo como um array associativo.
 */
return [
    'database' => [
        // Configuração para MySQL
        'host' => 'localhost',
        'dbname' => '', // Substitua pelo nome do seu banco de dados
        'user' => '',   // Substitua pelo nome do usuário do banco de dados
        'password' => '' // Substitua pela senha do banco de dados

        // Para SQLite, descomente a linha abaixo e ajuste o caminho
        // 'file_path' => __DIR__ . $ds . '..' . $ds . 'database.sqlite'
    ],

    // Exemplo de configuração adicional, como credenciais OAuth
    // 'google_oauth' => [
    //     'client_id' => 'client_id',
    //     'client_secret' => 'client_secret',
    //     'redirect_uri' => 'redirect_uri'
    // ],
];
