<?php

use App\core\database\PdoWrapper;
use App\core\router\Navigator;

/**
 * Configuração e inicialização da conexão com o banco de dados.
 *
 * - Este script utiliza a classe `PdoWrapper` para gerenciar a conexão com o banco de dados.
 * - As configurações do banco de dados são carregadas a partir de um arquivo de configuração.
 * - A conexão com o banco é registrada no objeto `Navigator` como uma dependência utilizável em toda a aplicação.
 */

// Carrega as configurações do arquivo de exemplo
$config = require __DIR__ . '/../../config/config_sample.php';

// Registra a conexão com o banco de dados no Navigator
Navigator::set('db', function () use ($config) {
    return new PdoWrapper(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['database']['host'],   // Host do banco de dados
            $config['database']['dbname'] // Nome do banco de dados
        ),
        $config['database']['user'],       // Usuário do banco de dados
        $config['database']['password']    // Senha do banco de dados
    );
});
