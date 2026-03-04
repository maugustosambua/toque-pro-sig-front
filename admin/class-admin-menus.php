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
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    // Carrega assets de estilos para as pÃ¡ginas do plugin.
    public static function enqueue_assets() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( '' === $page || 0 !== strpos( $page, 'tps-' ) ) {
            return;
        }

        $style_path = TPS_PLUGIN_PATH . 'assets/css/admin-modern-shared.css';
        $style_url  = plugins_url( 'assets/css/admin-modern-shared.css', TPS_PLUGIN_PATH . 'toque-pro-sig.php' );
        $version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : '1.0.0';

        wp_enqueue_style( 'tps-admin-modern-shared', $style_url, array(), $version );
    }

    public static function register_menus() {

        // Menu Dashboard
        add_menu_page(
            'Painel',
            'Painel',
            'manage_options',
            'tps-dashboard',
            array( __CLASS__, 'dashboard_page' ),
            'dashicons-chart-area',
            25
        );

        // Menu principal
        add_menu_page(
            'Clientes',
            'Clientes',
            'manage_options',
            'tps-customers',
            array( __CLASS__, 'customers_list_page' ),
            'dashicons-groups',
            26
        );

        // Submenu: Todos os Clientes
        add_submenu_page(
            'tps-customers',
            'Todos os Clientes',
            'Todos os Clientes',
            'manage_options',
            'tps-customers',
            array( __CLASS__, 'customers_list_page' )
        );

        // Submenu: Adicionar Novo
        add_submenu_page(
            'tps-customers',
            'Adicionar Cliente',
            'Adicionar Novo',
            'manage_options',
            'tps-customers-add',
            array( __CLASS__, 'customers_add_page' )
        );

        // Submenu: Importar Clientes
        add_submenu_page(
            'tps-customers',
            'Importar Clientes',
            'Importar',
            'manage_options',
            'tps-customers-import',
            array( __CLASS__, 'customers_import_page' )
        );

        // Menu Produtos e Serviços
        add_menu_page(
            'Produtos e Serviços',
            'Produtos/Serviços',
            'manage_options',
            'tps-products-services',
            array( __CLASS__, 'products_services_list_page' ),
            'dashicons-cart',
            27
        );

        // Submenu: Todos
        add_submenu_page(
            'tps-products-services',
            'Todos os Produtos e Serviços',
            'Todos os Itens',
            'manage_options',
            'tps-products-services',
            array( __CLASS__, 'products_services_list_page' )
        );

        // Submenu: Adicionar
        add_submenu_page(
            'tps-products-services',
            'Adicionar Produto ou Serviço',
            'Adicionar Novo',
            'manage_options',
            'tps-products-services-add',
            array( __CLASS__, 'products_services_add_page' )
        );

        // Menu Documentos
        add_menu_page(
            'Documentos',
            'Documentos',
            'manage_options',
            'tps-documents',
            array( __CLASS__, 'documents_list_page' ),
            'dashicons-media-document',
            28
        );

        // Submenu: Adicionar Documento
        add_submenu_page(
            'tps-documents',
            'Adicionar Documento',
            'Adicionar Documento',
            'manage_options',
            'tps-documents-add',
            array( __CLASS__, 'documents_add_page' )
        );

        // Menu Configurações
        add_menu_page(
            'Configurações',
            'Configurações',
            'manage_options',
            'tps-settings',
            array( __CLASS__, 'settings_page' ),
            'dashicons-admin-generic',
            29
        );

        // Página escondida para impressão
        add_submenu_page(
            null,
            'Imprimir Documento',
            'Imprimir Documento',
            'manage_options',
            'tps-documents-print',
            array( __CLASS__, 'documents_print_page' )
        );
    }

    // Mostra a lista de todos os clientes, com filtros e barra de pesquisa
    public static function customers_list_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-list-view.php';
    }

    // Dashboard ERP
    public static function dashboard_page() {
        require TPS_PLUGIN_PATH . 'modules/dashboard/dashboard-view.php';
    }

    // Mostra o formulário de criação/edição
    public static function customers_add_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-view.php';
    }

    // Mostra a pagina de importação
    public static function customers_import_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-import-view.php';
    }

    // Lista de produtos e serviços
    public static function products_services_list_page() {
        require TPS_PLUGIN_PATH . 'modules/products-services/products-services-list-view.php';
    }

    // Formulário de produto ou serviço
    public static function products_services_add_page() {
        require TPS_PLUGIN_PATH . 'modules/products-services/products-services-view.php';
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

