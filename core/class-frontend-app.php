<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Frontend_App {

    // Flag para evitar loops no template.
    private static $rendering_template = false;

    // Views permitidas no frontend.
    private static function views() {
        return array(
            'tps-dashboard'             => TPS_PLUGIN_PATH . 'modules/dashboard/dashboard-view.php',
            'tps-customers'             => TPS_PLUGIN_PATH . 'modules/customers/customers-list-view.php',
            'tps-customers-add'         => TPS_PLUGIN_PATH . 'modules/customers/customers-view.php',
            'tps-customers-import'      => TPS_PLUGIN_PATH . 'modules/customers/customers-import-view.php',
            'tps-products-services'     => TPS_PLUGIN_PATH . 'modules/products-services/products-services-list-view.php',
            'tps-products-services-add' => TPS_PLUGIN_PATH . 'modules/products-services/products-services-view.php',
            'tps-documents'             => TPS_PLUGIN_PATH . 'modules/documents/documents-list-view.php',
            'tps-documents-add'         => TPS_PLUGIN_PATH . 'modules/documents/documents-form-view.php',
            'tps-inventory'             => TPS_PLUGIN_PATH . 'modules/inventory/inventory-view.php',
            'tps-stock-movements'       => TPS_PLUGIN_PATH . 'modules/inventory/inventory-movements-view.php',
            'tps-payments'              => TPS_PLUGIN_PATH . 'modules/payments/payments-list-view.php',
            'tps-accounts-receivable'   => TPS_PLUGIN_PATH . 'modules/payments/accounts-receivable-view.php',
            'tps-users'                 => TPS_PLUGIN_PATH . 'modules/users/users-list-view.php',
            'tps-users-add'             => TPS_PLUGIN_PATH . 'modules/users/users-view.php',
            'tps-audit'                 => TPS_PLUGIN_PATH . 'modules/audit/audit-list-view.php',
            'tps-settings'              => TPS_PLUGIN_PATH . 'modules/settings/settings-view.php',
        );
    }

    // Inicializa o frontend.
    public static function init() {
        add_shortcode( 'tps_frontend', array( __CLASS__, 'render_shortcode' ) );
        add_filter( 'template_include', array( __CLASS__, 'maybe_use_plugin_template' ) );
        add_filter( 'show_admin_bar', array( __CLASS__, 'maybe_hide_admin_bar' ) );
    }

    // Renderiza o app no frontend.
    public static function render_shortcode( $atts = array() ) {
        if ( ! is_user_logged_in() ) {
            $redirect_to = '';

            if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
                $redirect_to = home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
            }

            wp_safe_redirect( wp_login_url( $redirect_to ) );
            exit;
        }

        if ( ! function_exists( 'tps_current_user_can_access_plugin' ) || ! tps_current_user_can_access_plugin() ) {
            return '<p>' . esc_html__( 'Nao tem permissao para aceder a esta area.', 'toque-pro-sig-front' ) . '</p>';
        }

        $atts = shortcode_atts(
            array(
                'view' => 'tps-dashboard',
            ),
            $atts,
            'tps_frontend'
        );

        $views = self::views();
        $page  = isset( $_GET['tps_view'] ) ? sanitize_key( wp_unslash( $_GET['tps_view'] ) ) : sanitize_key( $atts['view'] );
        if ( ! isset( $views[ $page ] ) ) {
            $page = self::get_first_accessible_page();
        }

        if ( ! self::current_user_can_access_page( $page ) ) {
            $page = self::get_first_accessible_page();
        }

        if ( '' === $page ) {
            return '<p>' . esc_html__( 'Nao tem permissao para aceder a esta area.', 'toque-pro-sig-front' ) . '</p>';
        }

        $base_url = get_permalink();
        if ( ! $base_url ) {
            $base_url = home_url( '/' );
        }

        tps_set_frontend_context( $base_url );
        self::enqueue_assets( $page );

        $notice = tps_get_notice_data_from_query();

        ob_start();
        ?>
        <div class="tps-frontend-app">
            <?php self::render_app_header(); ?>
            <?php self::render_navigation( $page ); ?>
            <div class="tps-app-content">
                <?php if ( ! empty( $notice ) ) : ?>
                    <?php tps_render_notice_from_query(); ?>
                <?php endif; ?>
                <?php require $views[ $page ]; ?>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        tps_clear_frontend_context();

        return $output;
    }

    // Usa template proprio do plugin em paginas com o shortcode.
    public static function maybe_use_plugin_template( $template ) {
        if ( is_admin() || self::$rendering_template ) {
            return $template;
        }

        if ( ! self::current_request_has_shortcode() ) {
            return $template;
        }

        $plugin_template = TPS_PLUGIN_PATH . 'core/frontend-template.php';

        return file_exists( $plugin_template ) ? $plugin_template : $template;
    }

    // Renderiza o conteudo principal do template proprio.
    public static function render_template_content() {
        global $post;

        self::$rendering_template = true;

        if ( $post instanceof WP_Post ) {
            echo do_shortcode( $post->post_content );
        }

        self::$rendering_template = false;
    }

    // Oculta a admin bar apenas na tela standalone do plugin.
    public static function maybe_hide_admin_bar( $show ) {
        if ( self::current_request_has_shortcode() ) {
            return false;
        }

        return $show;
    }

    // Carrega assets compartilhados da interface.
    private static function enqueue_assets( $page ) {
        wp_enqueue_style( 'dashicons' );
        self::enqueue_style( 'tps-tailwind-ui', 'assets/css/tailwind-ui.css' );
        self::enqueue_style( 'tps-ui-modern-overrides', 'assets/css/ui-modern-overrides.css', array( 'tps-tailwind-ui' ) );
        self::enqueue_script( 'tps-admin-pages', 'assets/js/admin-pages.js' );
        wp_localize_script( 'tps-admin-pages', 'tpsAdminData', self::build_page_data( $page ) );
    }

    // Header principal fixo da aplicacao.
    private static function render_app_header() {
        $settings     = get_option( 'tps_settings', array() );
        $company_name = isset( $settings['company_name'] ) && '' !== trim( (string) $settings['company_name'] ) ? (string) $settings['company_name'] : get_bloginfo( 'name' );
        $company_nuit = isset( $settings['company_nuit'] ) ? (string) $settings['company_nuit'] : '';
        $initials     = self::company_initials( $company_name );
        $current_user = wp_get_current_user();
        $logout_url   = wp_logout_url( wp_login_url() );
        $profile_url  = tps_get_page_url(
            'tps-users-add',
            array(
                'user_id' => $current_user instanceof WP_User ? (int) $current_user->ID : 0,
            )
        );
        ?>
        <header class="tps-app-header">
            <div class="tps-app-header-start">
                <button type="button" class="tps-app-menu-toggle" aria-expanded="false" aria-controls="tps-app-sidebar" aria-label="Abrir navegação">
                    <span class="dashicons dashicons-menu-alt" aria-hidden="true"></span>
                </button>
                <div class="tps-app-brand">
                <div class="tps-app-brand-mark" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
                <div class="tps-app-brand-copy">
                    <strong><?php echo esc_html( $company_name ); ?></strong>
                    <span><?php echo esc_html( '' !== $company_nuit ? 'NUIT ' . $company_nuit : 'Plataforma ERP' ); ?></span>
                </div>
            </div>
            </div>
            <div class="tps-app-header-meta">
                <a class="tps-app-header-link" href="<?php echo esc_url( $profile_url ); ?>">
                    <span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
                    <span><?php echo esc_html( $current_user instanceof WP_User ? $current_user->display_name : 'Utilizador' ); ?></span>
                </a>
                <a class="tps-app-header-link tps-app-header-link-logout" href="<?php echo esc_url( $logout_url ); ?>">
                    <span class="dashicons dashicons-migrate" aria-hidden="true"></span>
                    <span>Logout</span>
                </a>
            </div>
        </header>
        <?php
    }

    // Navegacao principal do frontend.
    private static function render_navigation( $page ) {
        $items = array(
            'tps-dashboard'         => array(
                'label' => 'Dashboard',
                'icon'  => 'dashicons-dashboard',
                'can'   => array( 'admin', 'emitir', 'cancelar', 'receber', 'exportar', 'fiscal' ),
            ),
            'tps-documents'         => array(
                'label' => 'Documentos',
                'icon'  => 'dashicons-media-text',
                'can'   => array( 'emitir', 'cancelar', 'exportar', 'fiscal' ),
            ),
            'tps-customers'         => array(
                'label' => 'Clientes',
                'icon'  => 'dashicons-groups',
                'can'   => array( 'admin' ),
            ),
            'tps-products-services' => array(
                'label' => 'Produtos/Servicos',
                'icon'  => 'dashicons-cart',
                'can'   => array( 'admin' ),
            ),
            'tps-inventory'         => array(
                'label' => 'Stock',
                'icon'  => 'dashicons-archive',
                'can'   => array( 'admin' ),
            ),
            'tps-payments'          => array(
                'label' => 'Recebimentos',
                'icon'  => 'dashicons-money-alt',
                'can'   => array( 'receber' ),
            ),
            'tps-users'             => array(
                'label' => 'Utilizadores',
                'icon'  => 'dashicons-admin-users',
                'can'   => array( 'admin' ),
            ),
            'tps-audit'             => array(
                'label' => 'Auditoria',
                'icon'  => 'dashicons-visibility',
                'can'   => array( 'admin', 'fiscal' ),
            ),
            'tps-settings'          => array(
                'label' => 'Configuracoes',
                'icon'  => 'dashicons-admin-generic',
                'can'   => array( 'admin' ),
            ),
        );

        $parents = array(
            'tps-customers-add'         => 'tps-customers',
            'tps-customers-import'      => 'tps-customers',
            'tps-products-services-add' => 'tps-products-services',
            'tps-documents-add'         => 'tps-documents',
            'tps-stock-movements'       => 'tps-inventory',
            'tps-accounts-receivable'   => 'tps-payments',
            'tps-users-add'             => 'tps-users',
        );

        $active = isset( $parents[ $page ] ) ? $parents[ $page ] : $page;
        ?>
        <button type="button" class="tps-app-sidebar-backdrop" hidden aria-label="Fechar navegação"></button>
        <aside id="tps-app-sidebar" class="tps-app-sidebar" aria-label="Navegacao principal" tabindex="-1">
            <div class="tps-sidebar-section-label">Navegacao principal</div>
            <nav class="tps-frontend-nav" aria-label="Toque Pro SiG">
                <?php foreach ( $items as $item_page => $item ) : ?>
                    <?php if ( empty( $item['can'] ) || ! tps_current_user_can_any( $item['can'] ) ) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <a class="tps-frontend-nav-link <?php echo esc_attr( $active === $item_page ? 'is-active' : '' ); ?>" href="<?php echo esc_url( tps_get_page_url( $item_page ) ); ?>">
                        <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?> tps-frontend-nav-icon" aria-hidden="true"></span>
                        <span class="tps-frontend-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <?php
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

    // Prepara dados do JavaScript por pagina.
    private static function build_page_data( $page ) {
        $data = array(
            'page'         => $page,
            'noticeActive' => isset( $_GET['tps_notice'] ) && '' !== sanitize_key( wp_unslash( $_GET['tps_notice'] ) ),
        );

        if ( 'tps-customers' === $page ) {
            $customers_export_url = tps_current_user_can( 'exportar' )
                ? wp_nonce_url( add_query_arg( 'action', 'tps_export_customers', tps_get_action_url() ), 'tps_export_customers' )
                : '';

            $data['customersList'] = array(
                'ajaxUrl'       => tps_get_ajax_url(),
                'nonce'         => wp_create_nonce( 'tps_ajax_customers_list' ),
                'exportBaseUrl' => $customers_export_url,
            );
        }

        if ( 'tps-customers-import' === $page ) {
            $data['customersImport'] = array( 'enabled' => true );
        }

        if ( 'tps-products-services' === $page ) {
            $data['productsServicesList'] = array(
                'ajaxUrl' => tps_get_ajax_url(),
                'nonce'   => wp_create_nonce( 'tps_ajax_products_services_list' ),
            );
        }

        if ( 'tps-inventory' === $page || 'tps-stock-movements' === $page ) {
            $data['inventoryModule'] = array(
                'enabled' => true,
                'ajaxUrl' => tps_get_ajax_url(),
                'nonce'   => wp_create_nonce( 'tps_search_inventory_products' ),
            );
        }

        if ( 'tps-documents' === $page ) {
            $documents_export_url = tps_current_user_can( 'exportar' )
                ? wp_nonce_url( add_query_arg( 'action', 'tps_export_documents', tps_get_action_url() ), 'tps_export_documents' )
                : '';
            $documents_fiscal_export_url = tps_current_user_can( 'fiscal' )
                ? wp_nonce_url( add_query_arg( 'action', 'tps_export_fiscal_at', tps_get_action_url() ), 'tps_export_fiscal_at' )
                : '';

            $data['documentsList'] = array(
                'ajaxUrl'       => tps_get_ajax_url(),
                'nonce'         => wp_create_nonce( 'tps_ajax_documents_list' ),
                'exportBaseUrl' => $documents_export_url,
                'exportFiscalBaseUrl' => $documents_fiscal_export_url,
            );
        }

        if ( 'tps-payments' === $page || 'tps-accounts-receivable' === $page ) {
            $data['paymentsModule'] = array( 'enabled' => true );
        }

        if ( 'tps-users' === $page ) {
            $data['usersList'] = array(
                'ajaxUrl' => tps_get_ajax_url(),
                'nonce'   => wp_create_nonce( 'tps_ajax_users_list' ),
            );
        }

        if ( 'tps-documents-add' === $page ) {
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
                    'ajaxUrl'      => tps_get_ajax_url(),
                    'nonce'        => wp_create_nonce( 'tps_search_customers' ),
                );
            }
        }

        if ( 'tps-dashboard' === $page && class_exists( 'TPS_Dashboard_Controller' ) ) {
            $dashboard_data     = TPS_Dashboard_Controller::get_dashboard_data();
            $data['dashboard'] = array(
                'monthlyRevenue' => isset( $dashboard_data['charts']['monthly_revenue'] ) ? $dashboard_data['charts']['monthly_revenue'] : array(),
            );
        }

        return $data;
    }

    // Verifica se a pagina consultada contem o shortcode do plugin.
    private static function current_request_has_shortcode() {
        if ( ! is_singular() ) {
            return false;
        }

        $post = get_queried_object();
        if ( ! ( $post instanceof WP_Post ) ) {
            return false;
        }

        if ( empty( $post->post_content ) ) {
            return false;
        }

        return has_shortcode( $post->post_content, 'tps_frontend' );
    }

    // Gera iniciais a partir do nome da empresa para o selo visual.
    private static function company_initials( $company_name ) {
        $parts = preg_split( '/\s+/', trim( (string) $company_name ) );
        $parts = array_filter( is_array( $parts ) ? $parts : array() );

        if ( empty( $parts ) ) {
            return 'TP';
        }

        $initials = '';
        foreach ( array_slice( $parts, 0, 2 ) as $part ) {
            $initials .= strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1 ) : substr( $part, 0, 1 ) );
        }

        return $initials;
    }

    // Resolve pagina inicial permitida para o perfil actual.
    private static function get_first_accessible_page() {
        $preferred_pages = array(
            'tps-dashboard',
            'tps-documents',
            'tps-payments',
            'tps-customers',
            'tps-products-services',
            'tps-inventory',
            'tps-users',
            'tps-audit',
            'tps-settings',
        );

        foreach ( $preferred_pages as $candidate ) {
            if ( self::current_user_can_access_page( $candidate ) ) {
                return $candidate;
            }
        }

        return '';
    }

    // Verifica permissao por pagina do frontend.
    private static function current_user_can_access_page( $page ) {
        $map = array(
            'tps-dashboard'             => array( 'admin', 'emitir', 'cancelar', 'receber', 'exportar', 'fiscal' ),
            'tps-documents'             => array( 'emitir', 'cancelar', 'exportar', 'fiscal' ),
            'tps-documents-add'         => array( 'emitir', 'cancelar', 'exportar', 'fiscal' ),
            'tps-payments'              => array( 'receber' ),
            'tps-accounts-receivable'   => array( 'receber' ),
            'tps-customers'             => array( 'admin' ),
            'tps-customers-add'         => array( 'admin' ),
            'tps-customers-import'      => array( 'admin' ),
            'tps-products-services'     => array( 'admin' ),
            'tps-products-services-add' => array( 'admin' ),
            'tps-inventory'             => array( 'admin' ),
            'tps-stock-movements'       => array( 'admin' ),
            'tps-users'                 => array( 'admin' ),
            'tps-users-add'             => array( 'admin' ),
            'tps-audit'                 => array( 'admin', 'fiscal' ),
            'tps-settings'              => array( 'admin' ),
        );

        if ( ! isset( $map[ $page ] ) ) {
            return false;
        }

        return tps_current_user_can_any( $map[ $page ] );
    }
}
