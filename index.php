<?php

declare(strict_types=1);

namespace APP;

require(__DIR__ . '/vendor/autoload.php');

// Importa dependências do projeto
require_once 'app/core/router/Navigator.php';
require_once 'app/core/database/index.php';

use App\core\router\Navigator;
use http\Env\Request;
use http\Env\Response;

/**
 * Inicialização do aplicativo principal.
 *
 * Este script configura o roteamento, verifica a existência do arquivo de configuração
 * e inicializa o servidor com as rotas necessárias.
 */

// Constante do separador de diretórios
$ds = DIRECTORY_SEPARATOR;

// Caminho para o arquivo de configuração
$config_file_path = __DIR__ . $ds . 'app' . $ds . 'config' . $ds . 'config_sample.php';


// Carrega o arquivo de configuração.
 $config = require($config_file_path);

 // Verifica se o arquivo de configuração existe
 if (file_exists($config_file_path) === false) {
        Navigator::halt(500, "Arquivo de configuração não encontrado. Crie um arquivo config.php no diretório app/config para começar..");
 }

/**
 * Inicializa o roteador da aplicação.
 * @var Navigator $router Objeto responsável por gerenciar as rotas.
 */
$router = Navigator::router();


/**
 * Define o comportamento padrão para rotas não permitidas.
 * Responde com um JSON indicando erro.
 */
$router->map('*', function () {


    Navigator::json([
        "tipo" => "suc",
        "resposta" => "Rota não permitida.!"
    ],200);


});




// Inicia o roteamento
Navigator::start();
