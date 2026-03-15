<?php

// modules/customers/class-customers-import-export.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Customers_Import_Export {
    // Regista os hooks de import/export.
    public static function init() {
        add_action( 'admin_post_tps_export_customer', array( __CLASS__, 'export_customer' ) );
        add_action( 'admin_post_tps_export_customers', array( __CLASS__, 'export_customers' ) );
        add_action( 'admin_post_tps_import_customers', array( __CLASS__, 'import' ) );
    }

    // Exporta um cliente especifico.
    public static function export_customer() {
        if ( ! tps_current_user_can( 'exportar' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_export_customer' );
        $customer_id = isset( $_GET['customer_id'] ) ? (int) $_GET['customer_id'] : 0;
        if ( ! $customer_id ) {
            wp_die( 'Cliente nao encontrado.' );
        }

        $customer = TPS_Customers_Model::get( $customer_id );
        if ( ! $customer ) {
            wp_die( 'Cliente nao encontrado.' );
        }

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        $filename = 'customer-' . $customer_id . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'type', 'name', 'nuit', 'email', 'phone', 'address', 'city' ) );
        fputcsv(
            $out,
            self::csv_safe_row(
                array(
                    $customer->type,
                    $customer->name,
                    $customer->nuit,
                    $customer->email,
                    $customer->phone,
                    $customer->address,
                    $customer->city,
                )
            )
        );
        fclose( $out );
        exit;
    }

    // Exporta clientes (todos ou filtrados pela lista).
    public static function export_customers() {
        if ( ! tps_current_user_can( 'exportar' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_export_customers' );
        $type      = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
        $search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $sort      = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'name';
        $orderby   = 'name';
        $order     = 'ASC';
        if ( 'city' === $sort ) {
            $orderby = 'city';
        } elseif ( 'date' === $sort ) {
            $orderby = 'created_at';
            $order   = 'DESC';
        }
        $customers = TPS_Customers_Model::get_customers(
            array(
                'type'     => $type,
                'search'   => $search,
                'orderby'  => $orderby,
                'order'    => $order,
                'per_page' => 0,
                'offset'   => 0,
            )
        );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=customers.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'type', 'name', 'nuit', 'email', 'phone', 'address', 'city' ) );
        foreach ( $customers as $customer ) {
            fputcsv(
                $out,
                self::csv_safe_row(
                    array(
                        $customer->type,
                        $customer->name,
                        $customer->nuit,
                        $customer->email,
                        $customer->phone,
                        $customer->address,
                        $customer->city,
                    )
                )
            );
        }

        fclose( $out );
        exit;
    }

    // Redirecciona com erro para a pagina de import.
    private static function redirect_import_error( $notice_code, $extra = array() ) {
        $url = tps_notice_url(
            tps_get_page_url( 'tps-customers-import' ),
            $notice_code,
            'error',
            $extra
        );
        wp_safe_redirect( esc_url_raw( $url ) );
        exit;
    }

    // Importa clientes via CSV.
    public static function import() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_import_customers' );

        if ( empty( $_FILES['file']['tmp_name'] ) ) {
            self::redirect_import_error( 'import_no_file' );
        }

        $file = $_FILES['file']['tmp_name'];
        $ext  = strtolower( pathinfo( $_FILES['file']['name'], PATHINFO_EXTENSION ) );
        if ( 'csv' !== $ext ) {
            self::redirect_import_error( 'import_invalid_file_type' );
        }

        $handle = fopen( $file, 'r' );
        if ( ! $handle ) {
            self::redirect_import_error( 'import_unable_to_open' );
        }

        $first_line = fgets( $handle );
        rewind( $handle );
        if ( false === $first_line ) {
            fclose( $handle );
            self::redirect_import_error( 'import_empty_file' );
        }

        $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';
        $header    = fgetcsv( $handle, 0, $delimiter );
        if ( empty( $header ) || ! is_array( $header ) ) {
            fclose( $handle );
            self::redirect_import_error( 'import_invalid_header' );
        }

        if ( isset( $header[0] ) ) {
            $header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
        }

        $header   = array_map( 'trim', $header );
        $expected = array( 'type', 'name', 'nuit', 'email', 'phone', 'address', 'city' );
        foreach ( $expected as $column ) {
            if ( ! in_array( $column, $header, true ) ) {
                fclose( $handle );
                self::redirect_import_error( 'import_missing_column', array( 'col' => $column ) );
            }
        }

        $indexes            = array_flip( $header );
        $imported           = 0;
        $skipped_duplicates = 0;
        $skipped_invalid    = 0;
        while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
            if ( 0 === count( array_filter( $row ) ) ) {
                continue;
            }

            if ( count( $row ) < count( $header ) ) {
                ++$skipped_invalid;
                continue;
            }

            $data = array(
                'type'    => sanitize_text_field( trim( $row[ $indexes['type'] ] ?? '' ) ),
                'name'    => sanitize_text_field( trim( $row[ $indexes['name'] ] ?? '' ) ),
                'nuit'    => sanitize_text_field( trim( $row[ $indexes['nuit'] ] ?? '' ) ),
                'email'   => sanitize_email( trim( $row[ $indexes['email'] ] ?? '' ) ),
                'phone'   => sanitize_text_field( trim( $row[ $indexes['phone'] ] ?? '' ) ),
                'address' => sanitize_textarea_field( trim( $row[ $indexes['address'] ] ?? '' ) ),
                'city'    => sanitize_text_field( trim( $row[ $indexes['city'] ] ?? '' ) ),
            );
            if ( empty( $data['name'] ) || empty( $data['type'] ) ) {
                ++$skipped_invalid;
                continue;
            }

            if ( ! in_array( $data['type'], array( 'individual', 'company' ), true ) ) {
                ++$skipped_invalid;
                continue;
            }

            if ( method_exists( 'TPS_Customers_Model', 'exists_duplicate' ) && TPS_Customers_Model::exists_duplicate( $data ) ) {
                ++$skipped_duplicates;
                continue;
            }

            TPS_Customers_Model::insert( $data );
            ++$imported;
        }

        fclose( $handle );

        $notice_type = ( $imported > 0 ) ? 'success' : 'warning';
        $url         = tps_notice_url(
            tps_get_page_url( 'tps-customers' ),
            'import_result',
            $notice_type,
            array(
                'imported'           => (int) $imported,
                'skipped_duplicates' => (int) $skipped_duplicates,
                'skipped_invalid'    => (int) $skipped_invalid,
            )
        );
        wp_safe_redirect( esc_url_raw( $url ) );
        exit;
    }

    private static function csv_safe_row( $row ) {
        if ( ! is_array( $row ) ) {
            return array();
        }

        return array_map( array( __CLASS__, 'csv_safe_cell' ), $row );
    }

    private static function csv_safe_cell( $value ) {
        $cell = (string) $value;

        if ( preg_match( '/^\s*[=+\-@]/', $cell ) ) {
            return "'" . $cell;
        }

        return $cell;
    }
}
