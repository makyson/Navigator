<?php
declare(strict_types=1);

namespace App\core\tracyextensions\debug\tracy;

/**
 * Classe abstrata base para extensões do Tracy Debugger.
 *
 * Fornece métodos e propriedades utilitárias para manipulação de strings,
 * renderização de valores e configuração de largura.
 */
abstract class ExtensionBase {

    /**
     * @var int $value_width Largura do elemento `<pre>` ao renderizar valores.
     */
    protected $value_width = 300;

    /**
     * Define a largura do elemento `<pre>` para renderização de valores.
     *
     * @param int $value_width Largura em pixels para o elemento `<pre>`.
     * @return void
     */
    public function setValueWidth(int $value_width): void {
        $this->value_width = $value_width;
    }

    /**
     * Manipula strings ou dados longos para torná-los clicáveis e expandíveis.
     *
     * Caso o valor seja muito grande, cria um link que permite expandir ou colapsar o conteúdo.
     *
     * @param mixed $value O valor a ser processado (pode ser string, array, objeto, etc.).
     * @return string Retorna o HTML formatado para exibição.
     */
    protected function handleLongStrings($value): string {
        if (is_array($value) === true || is_object($value) === true) {
            $value = print_r($value, true);
        }

        $value = is_bool($value) || is_int($value) ? var_export($value, true) : htmlspecialchars((string) $value);

        // Remove espaços em branco desnecessários para facilitar a leitura
        if (strpos($value, "\n") !== false) {
            $lines = explode("\n", $value);
            $value = '';
            foreach ($lines as $line) {
                $value .= trim($line) . "\n";
            }
        }

        if (strlen($value) > 60) {
            $uniq_id = uniqid('');
            $value = $this->ellipsis($value, 60) .
                ' <a href="#tracy-request-panel-' . $uniq_id .
                '" class="tracy-toggle tracy-collapsed">more</a>' .
                '<pre id="tracy-request-panel-' . $uniq_id .
                '" class="tracy-collapsed" style="max-width: ' . $this->value_width .
                'px; overflow: auto; min-height: 40px; background-color: #EEE; padding: 5px;">' .
                '<code>' . $value . '</code></pre>';
        }

        return $value;
    }

    /**
     * Limita o tamanho de uma string a um número específico de caracteres.
     *
     * Caso o texto exceda o limite, adiciona reticências (`...`) ao final.
     *
     * @param string $text Texto a ser truncado.
     * @param int $character_limit Número máximo de caracteres permitidos.
     * @return string Retorna a string truncada ou o texto original se não exceder o limite.
     */
    protected function ellipsis(string $text, int $character_limit = 30): string {
        return mb_strlen($text) > $character_limit ? mb_substr($text, 0, $character_limit) . '...' : $text;
    }
}
