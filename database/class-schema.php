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
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY customer_id (customer_id),
            KEY status (status)
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
            description TEXT NOT NULL,
            quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

}

