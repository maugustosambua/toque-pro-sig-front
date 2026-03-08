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

        // Interface do admin
        require_once TPS_PLUGIN_PATH . 'admin/class-admin-menus.php';

        // Utilitários
        require_once TPS_PLUGIN_PATH . 'helpers/tax.php';
        require_once TPS_PLUGIN_PATH . 'helpers/numbering.php';
        require_once TPS_PLUGIN_PATH . 'helpers/ui.php';


        // Módulo Clientes
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-import-export.php';

        // Módulo Documentos
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-document-lines-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-controller.php';

        // Módulo Produtos e Serviços
        require_once TPS_PLUGIN_PATH . 'modules/products-services/class-products-services-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/products-services/class-products-services-controller.php';

        // Módulo Configurações
        require_once TPS_PLUGIN_PATH . 'modules/settings/class-settings-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/dashboard/class-dashboard-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/login/class-login-customizer.php';


        // Inicializações
        self::init_controllers();
        self::init_admin();
    }

    // Inicializa controladores
    private static function init_controllers() {
        // Usado na inicializacao global para ligar os modulos do sistema
        // de clientes, documentos, produtos, configuracoes e login.
        TPS_Customers_Controller::init();
        TPS_Customers_Import_Export::init();
        TPS_Documents_Controller::init();
        TPS_Products_Services_Controller::init();
        TPS_Settings_Controller::init();
        TPS_Dashboard_Controller::init();
        TPS_Login_Customizer::init();
    }

    // Inicializa menus e interface do admin
    private static function init_admin() {
        // Usado apenas no wp-admin, evitando carregar menus no frontend.
        if ( is_admin() ) {
            TPS_Admin_Menus::init();
        }
    }
}
