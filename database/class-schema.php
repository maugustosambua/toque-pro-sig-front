<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Schema {

    // Cria todas as tabelas
    public static function install() {
        self::create_customers_table();
        self::create_documents_table();
        self::create_document_lines_table();
        self::create_products_services_table();
        self::create_stock_movements_table();
        self::create_payments_table();
        self::create_payment_allocations_table();
    }


    // Tabela de clientes
    public static function create_customers_table() {
        global $wpdb; // Objecto de acesso à base de dados

        $table = $wpdb->prefix . 'tps_customers'; // Nome da tabela
        $charset = $wpdb->get_charset_collate(); // Charset correcto

        // SQL de criação da tabela de clientes
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            name VARCHAR(191) NOT NULL,
            nuit VARCHAR(50) DEFAULT NULL,
            email VARCHAR(191) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            PRIMARY KEY  (id)
        ) {$charset};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // Carrega dbDelta
        dbDelta( $sql ); // Executa criação/actualização
    }

    // Tabela de documentos
    public static function create_documents_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'tps_documents';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            number BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            issue_date DATE NULL,
            due_date DATE NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            balance_due DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY due_date (due_date),
            KEY payment_status (payment_status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Cria tabela de linhas de documento
    public static function create_document_lines_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_document_lines';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id BIGINT UNSIGNED NOT NULL,
            product_service_id BIGINT UNSIGNED NULL DEFAULT NULL,
            description TEXT NOT NULL,
            quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY product_service_id (product_service_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de produtos e servicos
    public static function create_products_services_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_products_services';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            name VARCHAR(191) NOT NULL,
            sku VARCHAR(100) DEFAULT NULL,
            unit VARCHAR(50) DEFAULT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            track_stock TINYINT(1) NOT NULL DEFAULT 0,
            min_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
            stock_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
            cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY type (type),
            KEY name (name),
            KEY sku (sku),
            KEY track_stock (track_stock)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de movimentos de stock.
    public static function create_stock_movements_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_stock_movements';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            movement_type VARCHAR(20) NOT NULL,
            quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
            unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            reference_type VARCHAR(50) DEFAULT NULL,
            reference_id BIGINT UNSIGNED DEFAULT NULL,
            movement_date DATETIME NOT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY movement_type (movement_type),
            KEY reference_type (reference_type),
            KEY reference_id (reference_id),
            KEY movement_date (movement_date)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de recebimentos.
    public static function create_payments_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_payments';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            payment_date DATE NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            method VARCHAR(50) NOT NULL DEFAULT 'cash',
            reference VARCHAR(191) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY customer_id (customer_id),
            KEY payment_date (payment_date),
            KEY method (method)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de alocacoes de recebimentos.
    public static function create_payment_allocations_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_payment_allocations';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            payment_id BIGINT UNSIGNED NOT NULL,
            document_id BIGINT UNSIGNED NOT NULL,
            allocated_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY payment_id (payment_id),
            KEY document_id (document_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

}

