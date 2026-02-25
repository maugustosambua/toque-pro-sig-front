<?php

// modules/customers/class-customers-import-export.php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

class TPS_Customers_Import_Export
{
    // Regista os hooks de import/export
    public static function init()
    {
        add_action('admin_post_tps_export_customer', array( __CLASS__, 'export_customer' ));
        add_action('admin_post_tps_export_customers', array( __CLASS__, 'export_customers' ));
        add_action('admin_post_tps_import_customers', array( __CLASS__, 'import' ));
    }

    // Exporta um cliente específico
    public static function export_customer()
    {

        // Permissão
        if (! current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        // Segurança
        check_admin_referer('tps_export_customer');
        $customer_id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : 0;
        if (! $customer_id) {
            wp_die('Customer not found.');
        }

        $customer = TPS_Customers_Model::get($customer_id);
        if (! $customer) {
            wp_die('Customer not found.');
        }

        // Limpa qualquer output antes dos headers
        while (ob_get_level()) {
            ob_end_clean();
        }

        nocache_headers();
        $filename = 'customer-' . $customer_id . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        $out = fopen('php://output', 'w');
        fputcsv($out, array( 'type', 'name', 'nuit', 'email', 'phone', 'address', 'city' ));
        fputcsv($out, array(
            $customer->type,
            $customer->name,
            $customer->nuit,
            $customer->email,
            $customer->phone,
            $customer->address,
            $customer->city,
        ));
        fclose($out);
        exit;
    }

    // Exporta clientes (todos ou filtrados pela lista)
    public static function export_customers()
    {

        // Permissão
        if (! current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        // Segurança
        check_admin_referer('tps_export_customers');
        $type   = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $customers = TPS_Customers_Model::get_customers(array(
            'type'     => $type,
            'search'   => $search,
            'orderby'  => 'name',
            'order'    => 'ASC',
            'per_page' => 0,
            'offset'   => 0,
        ));
// Limpa qualquer output antes dos headers
        while (ob_get_level()) {
            ob_end_clean();
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=customers.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $out = fopen('php://output', 'w');
        fputcsv($out, array( 'type', 'name', 'nuit', 'email', 'phone', 'address', 'city' ));
        foreach ($customers as $c) {
            fputcsv($out, array(
                $c->type,
                $c->name,
                $c->nuit,
                $c->email,
                $c->phone,
                $c->address,
                $c->city,
            ));
        }

        fclose($out);
        exit;
    }

    // Redirecciona com erro para a página de import
    private static function redirect_import_error($error, $extra = array())
    {

        $args = array_merge(array(
                'page'  => 'tps-customers-import',
                'error' => $error,
            ), $extra);
        wp_safe_redirect(esc_url_raw(add_query_arg($args, admin_url('admin.php'))));
        exit;
    }

    // Importa clientes via CSV
    public static function import()
    {

        // Permissão
        if (! current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        // Segurança
        check_admin_referer('tps_import_customers');
// Ficheiro existe
        if (empty($_FILES['file']['tmp_name'])) {
            self::redirect_import_error('no_file');
        }

        $file = $_FILES['file']['tmp_name'];
        $ext  = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            self::redirect_import_error('invalid_file_type');
        }

        $handle = fopen($file, 'r');
        if (! $handle) {
            self::redirect_import_error('unable_to_open');
        }

        // Detecta delimitador ( , ou ; )
        $first_line = fgets($handle);
        rewind($handle);
        if ($first_line === false) {
            fclose($handle);
            self::redirect_import_error('empty_file');
        }

        $delimiter = ( substr_count($first_line, ';') > substr_count($first_line, ',') ) ? ';' : ',';
// Lê e normaliza header
        $header = fgetcsv($handle, 0, $delimiter);
        if (empty($header) || ! is_array($header)) {
            fclose($handle);
            self::redirect_import_error('invalid_header');
        }

        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        $header = array_map('trim', $header);
        $expected = array( 'type', 'name', 'nuit', 'email', 'phone', 'address', 'city' );
        foreach ($expected as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);
                self::redirect_import_error('missing_column', array( 'col' => $col ));
            }
        }

        $indexes = array_flip($header);
        $imported = 0;
        $skipped_duplicates = 0;
        $skipped_invalid = 0;
// Processa linhas do CSV
        while (( $row = fgetcsv($handle, 0, $delimiter) ) !== false) {
            if (count(array_filter($row)) === 0) {
                continue;
            }

            if (count($row) < count($header)) {
                $skipped_invalid++;
                continue;
            }

            $data = array(
                'type'    => sanitize_text_field(trim($row[ $indexes['type'] ] ?? '')),
                'name'    => sanitize_text_field(trim($row[ $indexes['name'] ] ?? '')),
                'nuit'    => sanitize_text_field(trim($row[ $indexes['nuit'] ] ?? '')),
                'email'   => sanitize_email(trim($row[ $indexes['email'] ] ?? '')),
                'phone'   => sanitize_text_field(trim($row[ $indexes['phone'] ] ?? '')),
                'address' => sanitize_textarea_field(trim($row[ $indexes['address'] ] ?? '')),
                'city'    => sanitize_text_field(trim($row[ $indexes['city'] ] ?? '')),
            );
            if (empty($data['name']) || empty($data['type'])) {
                $skipped_invalid++;
                continue;
            }

            if (! in_array($data['type'], array( 'individual', 'company' ), true)) {
                $skipped_invalid++;
                continue;
            }

            // Evita duplicação (NUIT + Nome)
            if (method_exists('TPS_Customers_Model', 'exists_duplicate') && TPS_Customers_Model::exists_duplicate($data)) {
                $skipped_duplicates++;
                continue;
            }

            TPS_Customers_Model::insert($data);
            $imported++;
        }

        fclose($handle);
// Redirecciona com feedback
        wp_safe_redirect(esc_url_raw(admin_url('admin.php?page=tps-customers' .
                    '&imported=' . (int) $imported .
                    '&skipped_duplicates=' . (int) $skipped_duplicates .
                    '&skipped_invalid=' . (int) $skipped_invalid)));
        exit;
    }
}
