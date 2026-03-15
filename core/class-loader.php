<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loader central do Toque Pro SiG
 * Responsável por carregar e inicializar todo o sistema
 */
class TPS_Loader {

    // Método principal
    public static function init() {
        // Usado no bootstrap do plugin para carregar classes e helpers
        // antes de qualquer modulo registrar hooks no WordPress.

        require_once TPS_PLUGIN_PATH . 'core/class-frontend-app.php';
        require_once TPS_PLUGIN_PATH . 'database/class-schema.php';

        // Utilitários
        require_once TPS_PLUGIN_PATH . 'helpers/capabilities.php';
        require_once TPS_PLUGIN_PATH . 'helpers/tax.php';
        require_once TPS_PLUGIN_PATH . 'helpers/numbering.php';
        require_once TPS_PLUGIN_PATH . 'helpers/ui.php';
        require_once TPS_PLUGIN_PATH . 'helpers/audit.php';

        // Garante que novas tabelas/colunas existam mesmo em installs ja activos.
        TPS_Schema::install();

        // Garante que perfis tenham as capabilities granulares esperadas.
        tps_maybe_sync_role_capabilities();

        // Ativa guardas de segurança para capacidades nativas de plugins/temas/core.
        tps_init_capability_guards();


        // Módulo Clientes
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-import-export.php';

        // Módulo Documentos
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-document-lines-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-controller.php';

        // MÃ³dulo Recebimentos
        require_once TPS_PLUGIN_PATH . 'modules/payments/class-payments-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/payments/class-payments-controller.php';

        // Módulo Produtos e Serviços
        require_once TPS_PLUGIN_PATH . 'modules/products-services/class-products-services-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/products-services/class-products-services-controller.php';

        // Modulo Estoque
        require_once TPS_PLUGIN_PATH . 'modules/inventory/class-inventory-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/inventory/class-inventory-controller.php';

        // Módulo Configurações
        require_once TPS_PLUGIN_PATH . 'modules/settings/class-settings-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/dashboard/class-dashboard-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/users/class-users-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/audit/class-audit-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/login/class-login-customizer.php';


        // Inicializações
        self::init_controllers();
        self::init_frontend();
    }

    // Inicializa controladores
    private static function init_controllers() {
        // Usado na inicializacao global para ligar os modulos do sistema
        // de clientes, documentos, produtos, configuracoes e login.
        TPS_Customers_Controller::init();
        TPS_Customers_Import_Export::init();
        TPS_Documents_Controller::init();
        TPS_Payments_Controller::init();
        TPS_Products_Services_Controller::init();
        TPS_Inventory_Controller::init();
        TPS_Settings_Controller::init();
        TPS_Dashboard_Controller::init();
        TPS_Users_Controller::init();
        TPS_Audit_Controller::init();
        TPS_Login_Customizer::init();
    }

    // Inicializa o app frontend via shortcode.
    private static function init_frontend() {
        TPS_Frontend_App::init();
    }
}
