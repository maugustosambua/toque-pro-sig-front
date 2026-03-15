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
        self::create_fiscal_events_table();
        self::create_audit_trail_table();
        self::create_fiscal_snapshots_table();
        self::create_fiscal_monthly_closures_table();
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
            original_document_id BIGINT UNSIGNED DEFAULT NULL,
            adjustment_reason TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            issue_date DATE NULL,
            due_date DATE NULL,
            withholding_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            withholding_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            balance_due DECIMAL(10,2) NOT NULL DEFAULT 0,
            fiscal_prev_hash CHAR(64) DEFAULT NULL,
            fiscal_hash CHAR(64) DEFAULT NULL,
            fiscal_hashed_at DATETIME NULL,
            cancel_reason TEXT DEFAULT NULL,
            cancelled_at DATETIME NULL,
            cancelled_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY customer_id (customer_id),
            KEY original_document_id (original_document_id),
            KEY status (status),
            KEY due_date (due_date),
            KEY payment_status (payment_status),
            KEY fiscal_hash (fiscal_hash),
            KEY cancelled_by (cancelled_by)
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
            tax_mode VARCHAR(20) NOT NULL DEFAULT 'taxable',
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            exemption_code VARCHAR(50) DEFAULT NULL,
            exemption_reason VARCHAR(191) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY product_service_id (product_service_id),
            KEY tax_mode (tax_mode),
            KEY exemption_code (exemption_code)
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

    // Tabela de eventos fiscais para auditoria.
    public static function create_fiscal_events_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_fiscal_events';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            payload LONGTEXT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            prev_event_hash CHAR(64) DEFAULT NULL,
            event_hash CHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY event_hash (event_hash),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de trilha de auditoria para eventos criticos.
    public static function create_audit_trail_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_audit_trail';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(80) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id BIGINT UNSIGNED DEFAULT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            before_state LONGTEXT NULL,
            after_state LONGTEXT NULL,
            meta LONGTEXT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de snapshots fiscais imutaveis por documento.
    public static function create_fiscal_snapshots_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_fiscal_snapshots';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            document_id BIGINT UNSIGNED NOT NULL,
            snapshot_type VARCHAR(30) NOT NULL,
            prev_snapshot_hash CHAR(64) DEFAULT NULL,
            snapshot_hash CHAR(64) NOT NULL,
            payload LONGTEXT NOT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY snapshot_hash (snapshot_hash),
            KEY created_at (created_at),
            UNIQUE KEY uq_doc_snapshot_hash (document_id, snapshot_hash)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Tabela de fechos fiscais mensais.
    public static function create_fiscal_monthly_closures_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'tps_fiscal_monthly_closures';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            period_ym CHAR(7) NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            documents_count INT UNSIGNED NOT NULL DEFAULT 0,
            issued_count INT UNSIGNED NOT NULL DEFAULT 0,
            cancelled_count INT UNSIGNED NOT NULL DEFAULT 0,
            subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
            iva DECIMAL(14,2) NOT NULL DEFAULT 0,
            withholding_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            payable_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            payments_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            open_balance_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            closure_prev_hash CHAR(64) DEFAULT NULL,
            closure_hash CHAR(64) DEFAULT NULL,
            payload LONGTEXT NULL,
            closed_by BIGINT UNSIGNED DEFAULT NULL,
            closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY period_ym (period_ym),
            KEY closure_hash (closure_hash),
            KEY closed_by (closed_by),
            KEY closed_at (closed_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

}

