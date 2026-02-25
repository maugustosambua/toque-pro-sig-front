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

        // Interface do admin
        require_once TPS_PLUGIN_PATH . 'admin/class-admin-menus.php';

        // Utilitários
        require_once TPS_PLUGIN_PATH . 'helpers/tax.php';
        require_once TPS_PLUGIN_PATH . 'helpers/formatting.php';
        require_once TPS_PLUGIN_PATH . 'helpers/numbering.php';
        require_once TPS_PLUGIN_PATH . 'helpers/ui.php';


        // Módulo Clientes
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-list-table.php';
        require_once TPS_PLUGIN_PATH . 'modules/customers/class-customers-import-export.php';

        // Módulo Documentos
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-document-lines-model.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-controller.php';
        require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-list-table.php';

        // Módulo Configurações
        require_once TPS_PLUGIN_PATH . 'modules/settings/class-settings-controller.php';


        // Inicializações
        self::init_controllers();
        self::init_filters();
        self::init_admin();
    }

    // Inicializa controladores
    private static function init_controllers() {
        TPS_Customers_Controller::init();
        TPS_Customers_Import_Export::init();
        TPS_Documents_Controller::init();
        TPS_Settings_Controller::init();
    }

    // Inicializa menus e interface do admin
    private static function init_admin() {
        if ( is_admin() ) {
            TPS_Admin_Menus::init();
        }
    }

    // Inicializa filtros
    private static function init_filters() {

    }
}
