<?php
declare(strict_types=1);

namespace App\core\tracyextensions\debug\tracy;

use App\core\tracyextensions\debug\database\PdoQueryCapture;
use App\core\router\Engine;
use App\core\router\Navigator;
use Throwable;
use Tracy\Debugger;

/**
 * Classe TracyExtensionLoader
 *
 * Responsável por carregar extensões para o Tracy Debugger no contexto da aplicação.
 */
class TracyExtensionLoader {

    /**
     * @var array $config Configuração adicional para as extensões.
     */
    protected array $config;

    /**
     * Construtor.
     *
     * Inicializa o carregador de extensões para o Tracy Debugger, adicionando
     * painéis e configurações específicas para a aplicação.
     *
     * @param Engine|null $app    Instância do motor Navigator. Se não fornecida, será recuperada automaticamente.
     * @param array       $config Configurações adicionais para as extensões.
     *
     * @throws \Exception Caso o Debugger não esteja habilitado.
     */
    public function __construct(Engine $app = null, array $config = []) {
        // Verifica se o Tracy Debugger está habilitado
        if (Debugger::isEnabled() === false) {
            Navigator::halt(500, "You need to enable Tracy\\Debugger before using this extension!");
        }

        // Se o motor não for fornecido, recupera a instância padrão
        if ($app === null) {
            $app = Navigator::app();
        }

        // Garante que os erros sejam tratados pelo Tracy Debugger
        $app->set('Navigator.handle_errors', false);

        // Define a configuração
        $this->config = $config;

        // Carrega as extensões
        $this->loadExtensions($app);
    }

    /**
     * Carrega as extensões do Tracy Debugger.
     *
     * Adiciona painéis personalizados e configurações específicas, como:
     * - Informações do Navigator
     * - Dados da requisição
     * - Dados da resposta
     * - Dados de sessão (se disponíveis)
     *
     * @param Engine $app Instância do motor Navigator.
     * @return void
     */
    protected function loadExtensions(Engine $app): void {
        // Adiciona os painéis básicos ao Debugger
        Debugger::getBar()->addPanel(new NavigatorPanelExtension($app));
        Debugger::getBar()->addPanel(new RequestExtension($app));
        Debugger::getBar()->addPanel(new ResponseExtension($app));

        // Adiciona o painel da sessão se os dados da sessão estiverem disponíveis
        if (session_status() === PHP_SESSION_ACTIVE || !empty($this->config['session_data'])) {
            $session_data = $this->config['session_data'] ?? $_SESSION;
            Debugger::getBar()->addPanel(new SessionExtension($session_data));
        }

        // Configura o painel adicional no BlueScreen
        Debugger::getBlueScreen()->addPanel(function(?Throwable $e) use ($app) {
            $NavigatorPanelExtension = new NavigatorPanelExtension($app);
            $NavigatorPanelExtension->setValueWidth(800);

            // Adiciona o painel de variáveis do Navigator ao BlueScreen
            if ($e instanceof Throwable && $e->getMessage()) {
                return [];
            }
            return [
                'tab' => 'Navigator Variables',
                'panel' => $NavigatorPanelExtension->getPanel(),
                'bottom' => true,
            ];
        });
    }
}
