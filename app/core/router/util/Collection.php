<?php

declare(strict_types=1);

namespace App\core\router\util;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;

/**
 * Classe Collection
 *
 * Permite acessar e manipular um conjunto de dados usando notação de array e objeto.
 * Implementa interfaces padrão do PHP para proporcionar funcionalidades como
 * iteração, contagem, serialização JSON e acesso por índice.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements Iterator<string, mixed>
 */
class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /**
     * Dados da coleção.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Construtor.
     *
     * Inicializa a coleção com um conjunto de dados opcional.
     *
     * @param array<string, mixed> $data Dados iniciais da coleção.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Obtém um item da coleção.
     *
     * @param string $key Chave do item.
     * @return mixed Valor associado à chave, ou `null` se a chave não existir.
     */
    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Define um item na coleção.
     *
     * @param string $key Chave do item.
     * @param mixed $value Valor a ser armazenado.
     */
    public function __set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Verifica se um item existe na coleção.
     *
     * @param string $key Chave do item.
     * @return bool `true` se o item existir, caso contrário `false`.
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove um item da coleção.
     *
     * @param string $key Chave do item.
     */
    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Obtém um item na posição especificada.
     *
     * @param string $offset Posição do item.
     * @return mixed Valor do item, ou `null` se não existir.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * Define um item na posição especificada.
     *
     * @param ?string $offset Posição do item. Se `null`, adiciona ao final.
     * @param mixed $value Valor do item.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Verifica se um item existe na posição especificada.
     *
     * @param string $offset Posição do item.
     * @return bool `true` se o item existir, caso contrário `false`.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Remove um item na posição especificada.
     *
     * @param string $offset Posição do item.
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Reinicia o ponteiro interno da coleção.
     */
    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * Obtém o valor atual da coleção.
     *
     * @return mixed Valor atual.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->data);
    }

    /**
     * Obtém a chave atual da coleção.
     *
     * @return mixed Chave atual.
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->data);
    }

    /**
     * Avança para o próximo item da coleção.
     */
    #[\ReturnTypeWillChange]
    public function next(): void
    {
        next($this->data);
    }

    /**
     * Verifica se a chave atual é válida.
     *
     * @return bool `true` se a chave atual for válida, caso contrário `false`.
     */
    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    /**
     * Conta o número de itens na coleção.
     *
     * @return int Número de itens.
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Obtém as chaves dos itens da coleção.
     *
     * @return array<int, string> Chaves dos itens.
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Obtém todos os dados da coleção.
     *
     * @return array<string, mixed> Dados da coleção.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Define os dados da coleção.
     *
     * @param array<string, mixed> $data Novos dados da coleção.
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Serializa os dados da coleção em formato JSON.
     *
     * @return array<string, mixed> Dados da coleção.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * Remove todos os itens da coleção.
     */
    public function clear(): void
    {
        $this->data = [];
    }
}
