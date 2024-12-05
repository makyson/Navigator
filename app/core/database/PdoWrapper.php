<?php

declare(strict_types=1);

namespace App\core\database;

use App\core\router\util\Collection;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Classe PdoWrapper
 *
 * Extensão da classe PDO para adicionar funcionalidades auxiliares, como execução simplificada de consultas
 * e manipulação de resultados.
 */
class PdoWrapper extends PDO
{
    /**
     * Construtor para inicializar a conexão com o banco de dados.
     *
     * Configura o PDO para usar o modo de exceção e o modo de busca associativa por padrão.
     *
     * @param string $dsn      Data Source Name, ex: 'mysql:host=localhost;dbname=testdb;charset=utf8mb4'
     * @param string $username Usuário do banco de dados
     * @param string $password Senha do banco de dados
     * @param array  $options  Opções adicionais para o PDO
     */
    public function __construct(string $dsn, string $username, string $password, array $options = [])
    {
        try {
            parent::__construct($dsn, $username, $password, $options);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Lide com erros de conexão
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Executa uma consulta SQL preparada com os parâmetros fornecidos.
     *
     * @param string $sql    A consulta SQL a ser executada.
     * @param array  $params Parâmetros para a consulta preparada.
     * @return PDOStatement Retorna a declaração PDO após execução.
     */
    public function runQuery(string $sql, array $params = []): PDOStatement
    {
        $processed_sql_data = $this->processInStatementSql($sql, $params);
        $sql = $processed_sql_data['sql'];
        $params = $processed_sql_data['params'];
        $statement = $this->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    /**
     * Retorna o primeiro campo do primeiro registro de uma consulta SQL.
     *
     * @param string $sql    A consulta SQL a ser executada.
     * @param array  $params Parâmetros para a consulta preparada.
     * @return mixed O valor do primeiro campo do primeiro registro.
     */
    public function fetchField(string $sql, array $params = [])
    {
        $result = $this->fetchRow($sql, $params);
        $data = $result->getData();
        return reset($data);
    }

    /**
     * Retorna o primeiro registro de uma consulta SQL.
     *
     * @param string $sql    A consulta SQL a ser executada.
     * @param array  $params Parâmetros para a consulta preparada.
     * @return Collection Retorna o registro como uma coleção.
     */
    public function fetchRow(string $sql, array $params = []): Collection
    {
        $sql .= stripos($sql, 'LIMIT') === false ? ' LIMIT 1' : '';
        $result = $this->fetchAll($sql, $params);
        return count($result) > 0 ? $result[0] : new Collection();
    }

    /**
     * Retorna todos os registros de uma consulta SQL.
     *
     * Cada registro é encapsulado em uma instância de `Collection`.
     *
     * @param string $sql    A consulta SQL a ser executada.
     * @param array  $params Parâmetros para a consulta preparada.
     * @return array Retorna os registros como um array de `Collection`.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $processed_sql_data = $this->processInStatementSql($sql, $params);
        $sql = $processed_sql_data['sql'];
        $params = $processed_sql_data['params'];
        $statement = $this->prepare($sql);
        $statement->execute($params);
        $results = $statement->fetchAll();

        if (is_array($results) && count($results) > 0) {
            foreach ($results as &$result) {
                $result = new Collection($result);
            }
        } else {
            $results = [];
        }
        return $results;
    }

    /**
     * Processa consultas SQL que contêm cláusulas `IN(?)` para substituir
     * o `?` por múltiplos marcadores de posição, caso os parâmetros fornecidos sejam arrays.
     *
     * @param string $sql    A consulta SQL a ser processada.
     * @param array  $params Parâmetros da consulta.
     * @return array Retorna um array contendo a consulta processada e os parâmetros ajustados.
     */
    protected function processInStatementSql(string $sql, array $params = []): array
    {
        $sql = preg_replace('/IN\s*\(\s*\?\s*\)/i', 'IN(?)', $sql);
        $current_index = 0;

        while (($current_index = strpos($sql, 'IN(?)', $current_index)) !== false) {
            $preceeding_count = substr_count($sql, '?', 0, $current_index - 1);
            $param = $params[$preceeding_count];
            $question_marks = '?';

            if (is_array($param)) {
                $question_marks = join(',', array_fill(0, count($param), '?'));
                $sql = substr_replace($sql, $question_marks, $current_index, 4);
                array_splice($params, $preceeding_count, 1, $param);
            }

            $current_index += strlen($question_marks);
        }

        return ['sql' => $sql, 'params' => $params];
    }
}
