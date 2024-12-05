<?php
declare(strict_types=1);

namespace App\core\tracyextensions\debug\tracy;

/**
 * Classe SessionExtension
 *
 * Implementa a interface \Tracy\IBarPanel para exibir dados da sessão no painel de depuração do Tracy Debugger.
 */
class SessionExtension extends ExtensionBase implements \Tracy\IBarPanel {

    /**
     * @var array $session_data Dados da sessão a serem exibidos.
     */
    protected array $session_data = [];

    /**
     * Construtor.
     *
     * Inicializa a classe com os dados da sessão. Se nenhum dado for passado,
     * os dados padrão da superglobal `$_SESSION` serão utilizados.
     *
     * @param array $session_data Dados da sessão.
     */
    public function __construct(array $session_data = []) {
        $this->session_data = $session_data ?: $_SESSION;
    }

    /**
     * Gera o conteúdo do painel Tracy.
     *
     * Renderiza os dados da sessão em uma tabela HTML para exibição no painel.
     * Caso a estrutura da sessão siga o padrão do Ghostff/Session,
     * processa os dados conforme necessário.
     *
     * @return string HTML contendo os dados da sessão formatados para o painel.
     */
    public function getPanel(): string {
        $session_data = $this->session_data;

        // Verifica se os dados seguem a estrutura do Ghostff/Session
        if (isset($session_data[':'][0])) {
            $session_data = $session_data[':'][0];
        }

        // Gera as linhas da tabela com os dados da sessão
        $table_tr_html = '';
        if (!empty($session_data)) {
            ksort($session_data, SORT_NATURAL);
            foreach ($session_data as $key => $value) {
                $table_tr_html .= '<tr><td>' . htmlspecialchars($key) . '</td><td>' . $this->handleLongStrings($value) . '</td></tr>' . "\n";
            }
        }

        // Monta o HTML final do painel
        $html = <<<EOT
            <h1>SESSION Data</h1> 
            <div class="tracy-inner" style="max-height: 400px;">
                <table>
                    <tbody>
                        {$table_tr_html}
                    </tbody>
                </table>
            </div>
            EOT;

        return $html;
    }

    /**
     * Gera a aba do painel Tracy.
     *
     * Renderiza o botão/ícone da aba exibido na barra de ferramentas do Tracy.
     *
     * @return string HTML representando a aba do painel.
     */
    public function getTab(): string {
        return <<<EOT
            <span title="Session Data">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="tan" class="bi bi-archive-fill" viewBox="0 0 16 16">
                    <path d="M12.643 15C13.979 15 15 13.845 15 12.5V5H1v7.5C1 13.845 2.021 15 3.357 15h9.286zM5.5 7h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zM.8 1a.8.8 0 0 0-.8.8V3a.8.8 0 0 0 .8.8h14.4A.8.8 0 0 0 16 3V1.8a.8.8 0 0 0-.8-.8H.8z"/>
                </svg>
                <span class="tracy-label">Session</span>
            </span>
            EOT;
    }
}
