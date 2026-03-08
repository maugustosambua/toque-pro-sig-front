<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classe responsavel pelos menus do admin.
class TPS_Admin_Menus {

    // Inicializa os hooks.
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    // Carrega assets para as paginas do plugin.
    public static function enqueue_assets() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( '' === $page || 0 !== strpos( $page, 'tps-' ) ) {
            return;
        }
        if ( 'tps-documents-print' === $page ) {
            return;
        }

        self::enqueue_style( 'tps-admin-modern-shared', 'assets/css/admin-modern-shared.css' );
        self::enqueue_style( 'tps-admin-pages', 'assets/css/admin-pages.css', array( 'tps-admin-modern-shared' ) );

        self::enqueue_script( 'tps-admin-pages', 'assets/js/admin-pages.js' );
        wp_localize_script( 'tps-admin-pages', 'tpsAdminData', self::build_admin_data( $page ) );
    }

    // Regista estilos versionados por filemtime.
    private static function enqueue_style( $handle, $relative_path, $deps = array() ) {
        $url     = tps_get_asset_url( $relative_path );
        $version = tps_get_asset_version( $relative_path );

        wp_enqueue_style( $handle, $url, $deps, $version );
    }

    // Regista scripts versionados por filemtime.
    private static function enqueue_script( $handle, $relative_path, $deps = array() ) {
        $url     = tps_get_asset_url( $relative_path );
        $version = tps_get_asset_version( $relative_path );

        wp_enqueue_script( $handle, $url, $deps, $version, true );
    }

    // Prepara dados para o JavaScript por pagina.
    private static function build_admin_data( $page ) {
        // Usado por assets/js/admin-pages.js para saber em que tela esta
        // e quais endpoints/nonces deve consumir em cada fluxo.
        $data = array(
            'page'        => $page,
            'noticeActive'=> isset( $_GET['tps_notice'] ) && '' !== sanitize_key( wp_unslash( $_GET['tps_notice'] ) ),
        );

        if ( 'tps-customers' === $page ) {
            $data['customersList'] = array(
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'tps_ajax_customers_list' ),
                'exportBaseUrl' => wp_nonce_url( admin_url( 'admin-post.php?action=tps_export_customers' ), 'tps_export_customers' ),
            );
        }

        if ( 'tps-customers-import' === $page ) {
            $data['customersImport'] = array( 'enabled' => true );
        }

        if ( 'tps-products-services' === $page ) {
            $data['productsServicesList'] = array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'tps_ajax_products_services_list' ),
            );
        }

        if ( 'tps-documents' === $page ) {
            $data['documentsList'] = array(
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'tps_ajax_documents_list' ),
                'exportBaseUrl' => wp_nonce_url( admin_url( 'admin-post.php?action=tps_export_documents' ), 'tps_export_documents' ),
            );
        }

        if ( 'tps-documents-add' === $page ) {
            // Usado na tela "Adicionar Documento", que troca o JS
            // conforme o utilizador esteja a criar ou editar.
            $document_id = isset( $_GET['document_id'] ) ? absint( $_GET['document_id'] ) : 0;
            if ( $document_id > 0 ) {
                $catalog_items_payload = array();

                if ( class_exists( 'TPS_Products_Services_Model' ) ) {
                    $catalog_items = TPS_Products_Services_Model::get_items(
                        array(
                            'orderby'  => 'name',
                            'order'    => 'ASC',
                            'per_page' => 1000,
                            'offset'   => 0,
                        )
                    );

                    foreach ( $catalog_items as $catalog_item ) {
                        $label = (string) $catalog_item->name;
                        if ( ! empty( $catalog_item->sku ) ) {
                            $label .= ' - ' . (string) $catalog_item->sku;
                        }

                        $catalog_items_payload[] = array(
                            'id'    => (int) $catalog_item->id,
                            'name'  => (string) $catalog_item->name,
                            'price' => (float) $catalog_item->price,
                            'label' => $label,
                        );
                    }
                }

                $data['documentsForm'] = array(
                    'mode'         => 'edit',
                    'catalogItems' => $catalog_items_payload,
                );
            } else {
                $has_customers = class_exists( 'TPS_Customers_Model' ) ? ( TPS_Customers_Model::count_customers() > 0 ) : false;

                $data['documentsForm'] = array(
                    'mode'         => 'create',
                    'hasCustomers' => $has_customers,
                    'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                    'nonce'        => wp_create_nonce( 'tps_search_customers' ),
                );
            }
        }

        if ( 'tps-dashboard' === $page && class_exists( 'TPS_Dashboard_Controller' ) ) {
            $dashboard_data             = TPS_Dashboard_Controller::get_dashboard_data();
            $data['dashboard'] = array(
                'monthlyRevenue' => isset( $dashboard_data['charts']['monthly_revenue'] ) ? $dashboard_data['charts']['monthly_revenue'] : array(),
            );
        }

        return $data;
    }

    public static function register_menus() {
        // Usado para expor no admin as telas de Dashboard, Clientes,
        // Produtos/Servicos, Documentos, Configuracoes e Impressao.

        // Menu Dashboard.
        add_menu_page(
            'Painel',
            'Painel',
            'manage_options',
            'tps-dashboard',
            array( __CLASS__, 'dashboard_page' ),
            'dashicons-chart-area',
            25
        );

        // Menu principal.
        add_menu_page(
            'Clientes',
            'Clientes',
            'manage_options',
            'tps-customers',
            array( __CLASS__, 'customers_list_page' ),
            'dashicons-groups',
            26
        );

        // Submenu: Todos os Clientes.
        add_submenu_page(
            'tps-customers',
            'Todos os Clientes',
            'Todos os Clientes',
            'manage_options',
            'tps-customers',
            array( __CLASS__, 'customers_list_page' )
        );

        // Submenu: Adicionar Novo.
        add_submenu_page(
            'tps-customers',
            'Adicionar Cliente',
            'Adicionar Novo',
            'manage_options',
            'tps-customers-add',
            array( __CLASS__, 'customers_add_page' )
        );

        // Submenu: Importar Clientes.
        add_submenu_page(
            'tps-customers',
            'Importar Clientes',
            'Importar',
            'manage_options',
            'tps-customers-import',
            array( __CLASS__, 'customers_import_page' )
        );

        // Menu Produtos e Servicos.
        add_menu_page(
            'Produtos e Servicos',
            'Produtos/Servicos',
            'manage_options',
            'tps-products-services',
            array( __CLASS__, 'products_services_list_page' ),
            'dashicons-cart',
            27
        );

        // Submenu: Todos.
        add_submenu_page(
            'tps-products-services',
            'Todos os Produtos e Servicos',
            'Todos os Itens',
            'manage_options',
            'tps-products-services',
            array( __CLASS__, 'products_services_list_page' )
        );

        // Submenu: Adicionar.
        add_submenu_page(
            'tps-products-services',
            'Adicionar Produto ou Servico',
            'Adicionar Novo',
            'manage_options',
            'tps-products-services-add',
            array( __CLASS__, 'products_services_add_page' )
        );

        // Menu Documentos.
        add_menu_page(
            'Documentos',
            'Documentos',
            'manage_options',
            'tps-documents',
            array( __CLASS__, 'documents_list_page' ),
            'dashicons-media-document',
            28
        );

        // Submenu: Adicionar Documento.
        add_submenu_page(
            'tps-documents',
            'Adicionar Documento',
            'Adicionar Documento',
            'manage_options',
            'tps-documents-add',
            array( __CLASS__, 'documents_add_page' )
        );

        // Menu Configuracoes.
        add_menu_page(
            'Configuracoes',
            'Configuracoes',
            'manage_options',
            'tps-settings',
            array( __CLASS__, 'settings_page' ),
            'dashicons-admin-generic',
            29
        );

        // Pagina escondida para impressao.
        add_submenu_page(
            null,
            'Imprimir Documento',
            'Imprimir Documento',
            'manage_options',
            'tps-documents-print',
            array( __CLASS__, 'documents_print_page' )
        );
    }

    // Mostra a lista de todos os clientes, com filtros e barra de pesquisa.
    public static function customers_list_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-list-view.php';
    }

    // Dashboard ERP.
    public static function dashboard_page() {
        require TPS_PLUGIN_PATH . 'modules/dashboard/dashboard-view.php';
    }

    // Mostra o formulario de criacao/edicao.
    public static function customers_add_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-view.php';
    }

    // Mostra a pagina de importacao.
    public static function customers_import_page() {
        require TPS_PLUGIN_PATH . 'modules/customers/customers-import-view.php';
    }

    // Lista de produtos e servicos.
    public static function products_services_list_page() {
        require TPS_PLUGIN_PATH . 'modules/products-services/products-services-list-view.php';
    }

    // Formulario de produto ou servico.
    public static function products_services_add_page() {
        require TPS_PLUGIN_PATH . 'modules/products-services/products-services-view.php';
    }

    // Lista de documentos.
    public static function documents_list_page() {
        require TPS_PLUGIN_PATH . 'modules/documents/documents-list-view.php';
    }

    // Formulario de documento.
    public static function documents_add_page() {
        require TPS_PLUGIN_PATH . 'modules/documents/documents-form-view.php';
    }

    // Pagina de configuracoes.
    public static function settings_page() {
        require TPS_PLUGIN_PATH . 'modules/settings/settings-view.php';
    }

    // Pagina de impressao do documento.
    public static function documents_print_page() {
        require TPS_PLUGIN_PATH . 'modules/documents/documents-print-view.php';
    }
}
