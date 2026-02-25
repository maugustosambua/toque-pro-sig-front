<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classe responsável pelos menus do admin
class TPS_Admin_Menus {

    // Inicializa os hooks
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
    }

    public static function register_menus() {

        // Menu principal
        $hook = add_menu_page(
            'Customers',
            'Customers',
            'manage_options',
            'tps-customers',
            array( __CLASS__, 'customers_list_page' ),
            'dashicons-groups',
            26
        );
    
        // Submenu: Todos os Clientes
        add_submenu_page(
            'tps-customers',
            'All Customers',
            'All Customers',
            'manage_options',
            'tps-customers',
            array( __CLASS__, 'customers_list_page' )
        );
    
        // Submenu: Adicionar Novo
        add_submenu_page(
            'tps-customers',
            'Add Customer',
            'Add New',
            'manage_options',
            'tps-customers-add',
            array( __CLASS__, 'customers_add_page' )
        );

        // Submenu: Importar Clientes
        add_submenu_page(
            'tps-customers',
            'Import Customers',
            'Import',
            'manage_options',
            'tps-customers-import',
            array( __CLASS__, 'customers_import_page' )
        );

        // Menu Documentos
        add_menu_page(
            'Documents',
            'Documents',
            'manage_options',
            'tps-documents',
            array( __CLASS__, 'documents_list_page' ),
            'dashicons-media-document',
            27
        );

        // Submenu: Adicionar Documento
        add_submenu_page(
            'tps-documents',
            'Add Document',
            'Add Document',
            'manage_options',
            'tps-documents-add',
            array( __CLASS__, 'documents_add_page' )
        );

        // Menu Configurações
        add_menu_page(
            'Settings',
            'Settings',
            'manage_options',
            'tps-settings',
            array( __CLASS__, 'settings_page' ),
            'dashicons-admin-generic',
            28
        );

        // Página escondida para impressão
        add_submenu_page(
            null,
            'Print Document',
            'Print Document',
            'manage_options',
            'tps-documents-print',
            array( __CLASS__, 'documents_print_page' )
        );

        
    }

    // Mostra a lista de todos os clientes, com filtros e barra de pesquisa 
    public static function customers_list_page() {
        // Vista
        require TPS_PLUGIN_PATH . 'modules/customers/customers-list-view.php';
    }

    // Mostra o formulário de criação/edição
    public static function customers_add_page() {
        // Vista (formulário)
        require TPS_PLUGIN_PATH . 'modules/customers/customers-view.php';
    }

    //Mostra a pagina de importação
    public static function customers_import_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-import-view.php';
    }

    // Lista de documentos
    public static function documents_list_page() {
        require TPS_PLUGIN_PATH . 'modules/documents/documents-list-view.php';
    }

    // Formulário de documento
    public static function documents_add_page() {
        require TPS_PLUGIN_PATH . 'modules/documents/documents-form-view.php';
    }


    // Página de configurações
    public static function settings_page() {
        require TPS_PLUGIN_PATH . 'modules/settings/settings-view.php';
    }

    // Página de impressão do documento
    public static function documents_print_page() {
        require TPS_PLUGIN_PATH . 'modules/documents/documents-print-view.php';
    }


}
