<?php
declare(strict_types=1);

namespace App\core\tracyextensions\debug\tracy;

use App\core\router\Engine;

/**
 * Classe NavigatorPanelExtension
 *
 * Implementa a interface \Tracy\IBarPanel para exibir informações do navegador
 * e rotas no painel de depuração do Tracy Debugger.
 */
class NavigatorPanelExtension extends ExtensionBase implements \Tracy\IBarPanel {

    /**
     * @var Engine $app Instância do motor da aplicação.
     */
    protected Engine $app;

    /**
     * Construtor.
     *
     * @param Engine $app Instância do motor da aplicação.
     */
    public function __construct(Engine $app) {
        $this->app = $app;
    }

    /**
     * Gera o conteúdo do painel Tracy.
     *
     * Renderiza os dados do navegador e informações detalhadas sobre a rota atual.
     *
     * @return string HTML contendo as informações formatadas para exibição no painel.
     */
    public function getPanel(): string {
        // Obtem variáveis do Navigator e dados da rota atual
        $Navigator_var_data = $this->app->get();
        $current_route = $this->app->router()->current();

        // Inicializa variáveis da rota
        $methods = '';
        $params = '';
        $pattern = '';
        $alias = '';
        $regex = '';
        $splat = '';

        // Preenche dados da rota atual, se disponível
        if ($current_route) {
            $methods = implode(', ', $current_route->methods);
            $params = $current_route->params ? print_r($current_route->params, true) : '';
            $pattern = $current_route->pattern;
            $alias = $current_route->alias;
            $regex = $current_route->regex;
            $splat = $current_route->splat;
        }

        // Adiciona dados da rota atual ao Navigator_var_data
        $Navigator_var_data['Current Route'] = <<<TEXT
            Pattern: {$pattern}
            Methods: {$methods}
            Params:  {$params}
            Alias:   {$alias}
            Regex:   {$regex}
            Splat:   {$splat}
            TEXT;

        // Converte objetos para strings legíveis
        foreach ($Navigator_var_data as $key => &$data) {
            if (is_object($data)) {
                $data = get_class($data) . ' Class';
            }
        }

        // Ordena os dados por chave
        ksort($Navigator_var_data, SORT_NATURAL);

        // Monta o HTML da tabela com as informações
        $table_tr_html = '';
        foreach ($Navigator_var_data as $key => $value) {
            $table_tr_html .= '<tr><td>' . $key . '</td><td>' . $this->handleLongStrings($value) . '</td></tr>' . "\n";
        }

        // HTML final do painel
        $html = <<<EOT
            <h1>Navigator Data</h1> 
            <div class="tracy-inner" style="max-height: 400px; overflow: auto;">
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
     * Cria um botão/ícone para a aba do painel na barra de ferramentas do Tracy.
     *
     * @return string HTML representando a aba do painel.
     */
    public function getTab(): string {
        return <<<EOT
            <span title="Navigator Vars">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="indigo" class="bi bi-file-zip-fill" viewBox="0 0 16 16">
                    <path d="M8.5 9.438V8.5h-1v.938a1 1 0 0 1-.03.243l-.4 1.598.93.62.93-.62-.4-1.598a1 1 0 0 1-.03-.243z"/>
                    <path d="M4 0h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm2.5 8.5v.938l-.4 1.599a1 1 0 0 0 .416 1.074l.93.62a1 1 0 0 0 1.109 0l.93-.62a1 1 0 0 0 .415-1.074l-.4-1.599V8.5a1 1 0 0 0-1-1h-1a1 1 0 0 0-1 1zm1-5.5h-1v1h1v1h-1v1h1v1H9V6H8V5h1V4H8V3h1V2H8V1H6.5v1h1v1z"/>
                </svg>
                <span class="tracy-label">Navigator</span>
            </span>
            EOT;
    }
}
