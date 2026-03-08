<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controlador de metricas para o Dashboard ERP.
 */
class TPS_Dashboard_Controller {

    // Inicializa o modulo.
    public static function init() {
        // Sem hooks por agora.
    }

    // Retorna todos os dados necessarios para a view.
    public static function get_dashboard_data() {
        $now                  = new DateTimeImmutable( 'now', wp_timezone() );
        $month_start          = $now->modify( 'first day of this month' )->format( 'Y-m-d' );
        $next_month_start     = $now->modify( 'first day of next month' )->format( 'Y-m-d' );
        $previous_month_start = $now->modify( 'first day of previous month' )->format( 'Y-m-d' );
        $month_before_start   = $now->modify( 'first day of -2 month' )->format( 'Y-m-d' );
        $last_90_days_start   = $now->modify( '-89 days' )->format( 'Y-m-d' );

        $status_counts = TPS_Documents_Model::count_by_status();

        $totals_current = self::issued_totals_between( $month_start, $next_month_start );
        $totals_prev    = self::issued_totals_between( $previous_month_start, $month_start );

        $issued_current_count = self::issued_documents_count_between( $month_start, $next_month_start );
        $average_ticket       = $issued_current_count > 0 ? ( $totals_current['total'] / $issued_current_count ) : 0.0;

        $monthly_revenue = self::monthly_revenue_series( 6 );

        return array(
            'period'            => array(
                'month_start'          => $month_start,
                'next_month_start'     => $next_month_start,
                'previous_month_start' => $previous_month_start,
                'month_before_start'   => $month_before_start,
            ),
            'kpis'               => array(
                'customers_total'        => (int) TPS_Customers_Model::count_customers(),
                'customers_new_month'    => self::new_customers_between( $month_start, $next_month_start ),
                'documents_total'        => (int) $status_counts['all'],
                'documents_draft'        => (int) $status_counts['draft'],
                'documents_issued'       => (int) $status_counts['issued'],
                'documents_cancelled'    => (int) $status_counts['cancelled'],
                'issued_count_month'     => $issued_current_count,
                'issued_count_prev'      => self::issued_documents_count_between( $previous_month_start, $month_start ),
                'revenue_total_month'    => (float) $totals_current['total'],
                'revenue_total_prev'     => (float) $totals_prev['total'],
                'revenue_subtotal_month' => (float) $totals_current['subtotal'],
                'revenue_subtotal_prev'  => (float) $totals_prev['subtotal'],
                'tax_month'              => (float) $totals_current['tax'],
                'average_ticket_month'   => (float) $average_ticket,
                'receivable_total'       => self::receivable_total(),
                'overdue_total'          => self::overdue_total(),
                'critical_products'      => class_exists( 'TPS_Products_Services_Model' ) ? (int) TPS_Products_Services_Model::count_critical_products() : 0,
            ),
            'charts'             => array(
                'monthly_revenue'  => $monthly_revenue,
                'status_breakdown' => array(
                    'draft'     => (int) $status_counts['draft'],
                    'issued'    => (int) $status_counts['issued'],
                    'cancelled' => (int) $status_counts['cancelled'],
                ),
                'type_breakdown'   => self::type_breakdown_between( $month_start, $next_month_start ),
            ),
            'top_customers'      => self::top_customers_between( $last_90_days_start, $next_month_start, 6 ),
            'recent_documents'   => self::recent_documents( 8 ),
            'critical_products'  => self::critical_products( 6 ),
            'last_90_days_start' => $last_90_days_start,
        );
    }

    // Total de documentos emitidos no intervalo.
    private static function issued_documents_count_between( $start_date, $end_date ) {
        global $wpdb;

        $table = TPS_Documents_Model::table();

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s AND issue_date >= %s AND issue_date < %s",
            'issued',
            $start_date,
            $end_date
        );

        return (int) $wpdb->get_var( $sql );
    }

    // Clientes criados no intervalo.
    private static function new_customers_between( $start_date, $end_date ) {
        global $wpdb;

        $table = TPS_Customers_Model::table();

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND created_at < %s",
            $start_date . ' 00:00:00',
            $end_date . ' 00:00:00'
        );

        return (int) $wpdb->get_var( $sql );
    }

    // Subtotal, IVA e total de documentos emitidos no intervalo.
    private static function issued_totals_between( $start_date, $end_date ) {
        global $wpdb;

        $docs_table  = TPS_Documents_Model::table();
        $lines_table = TPS_Document_Lines_Model::table();
        $tax_rate    = tps_get_iva_rate();

        $sql = $wpdb->prepare(
            "SELECT COALESCE(SUM(l.quantity * l.unit_price), 0) AS subtotal
             FROM {$docs_table} d
             LEFT JOIN {$lines_table} l ON l.document_id = d.id
             WHERE d.status = %s AND d.issue_date >= %s AND d.issue_date < %s",
            'issued',
            $start_date,
            $end_date
        );

        $subtotal = (float) $wpdb->get_var( $sql );
        $tax      = (float) ( $subtotal * $tax_rate );

        return array(
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'total'    => $subtotal + $tax,
        );
    }

    // Serie mensal de receita (ultimos N meses, incluindo o atual).
    private static function monthly_revenue_series( $months ) {
        global $wpdb;

        $months = max( 2, (int) $months );
        $now    = new DateTimeImmutable( 'now', wp_timezone() );

        $start_cursor = $now->modify( 'first day of this month' )->modify( '-' . ( $months - 1 ) . ' month' );
        $end_cursor   = $now->modify( 'first day of next month' );

        $labels_map = array();
        $cursor     = $start_cursor;
        while ( $cursor < $end_cursor ) {
            $ym               = $cursor->format( 'Y-m' );
            $labels_map[ $ym ] = array(
                'label'    => $cursor->format( 'M/Y' ),
                'subtotal' => 0.0,
                'total'    => 0.0,
                'docs'     => 0,
            );
            $cursor = $cursor->modify( '+1 month' );
        }

        $docs_table  = TPS_Documents_Model::table();
        $lines_table = TPS_Document_Lines_Model::table();

        $sql = $wpdb->prepare(
            "SELECT DATE_FORMAT(d.issue_date, '%%Y-%%m') AS ym,
                    COALESCE(SUM(l.quantity * l.unit_price), 0) AS subtotal,
                    COUNT(DISTINCT d.id) AS docs
             FROM {$docs_table} d
             LEFT JOIN {$lines_table} l ON l.document_id = d.id
             WHERE d.status = %s
               AND d.issue_date >= %s
               AND d.issue_date < %s
             GROUP BY ym
             ORDER BY ym ASC",
            'issued',
            $start_cursor->format( 'Y-m-d' ),
            $end_cursor->format( 'Y-m-d' )
        );

        $rows     = $wpdb->get_results( $sql );
        $tax_rate = tps_get_iva_rate();

        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $key = (string) $row->ym;
                if ( ! isset( $labels_map[ $key ] ) ) {
                    continue;
                }
                $subtotal                   = (float) $row->subtotal;
                $labels_map[ $key ]['subtotal'] = $subtotal;
                $labels_map[ $key ]['total']    = $subtotal + ( $subtotal * $tax_rate );
                $labels_map[ $key ]['docs']     = (int) $row->docs;
            }
        }

        return array_values( $labels_map );
    }

    // Distribuicao por tipo no intervalo.
    private static function type_breakdown_between( $start_date, $end_date ) {
        global $wpdb;

        $docs_table  = TPS_Documents_Model::table();
        $lines_table = TPS_Document_Lines_Model::table();
        $types       = TPS_Documents_Model::types();
        $tax_rate    = tps_get_iva_rate();

        $result = array();
        foreach ( $types as $type_key => $type_label ) {
            $result[ $type_key ] = array(
                'type'     => $type_key,
                'label'    => $type_label,
                'docs'     => 0,
                'subtotal' => 0.0,
                'total'    => 0.0,
            );
        }

        $sql = $wpdb->prepare(
            "SELECT d.type,
                    COUNT(DISTINCT d.id) AS docs,
                    COALESCE(SUM(l.quantity * l.unit_price), 0) AS subtotal
             FROM {$docs_table} d
             LEFT JOIN {$lines_table} l ON l.document_id = d.id
             WHERE d.status = %s
               AND d.issue_date >= %s
               AND d.issue_date < %s
             GROUP BY d.type",
            'issued',
            $start_date,
            $end_date
        );

        $rows = $wpdb->get_results( $sql );
        foreach ( $rows as $row ) {
            $type = (string) $row->type;
            if ( ! isset( $result[ $type ] ) ) {
                continue;
            }
            $subtotal                   = (float) $row->subtotal;
            $result[ $type ]['docs']     = (int) $row->docs;
            $result[ $type ]['subtotal'] = $subtotal;
            $result[ $type ]['total']    = $subtotal + ( $subtotal * $tax_rate );
        }

        return array_values( $result );
    }

    // Top clientes por faturacao emitida no intervalo.
    private static function top_customers_between( $start_date, $end_date, $limit ) {
        global $wpdb;

        $docs_table      = TPS_Documents_Model::table();
        $lines_table     = TPS_Document_Lines_Model::table();
        $customers_table = TPS_Customers_Model::table();
        $tax_rate        = tps_get_iva_rate();
        $limit           = max( 1, (int) $limit );

        $sql = $wpdb->prepare(
            "SELECT c.id AS customer_id,
                    c.name AS customer_name,
                    c.nuit AS customer_nuit,
                    COUNT(DISTINCT d.id) AS docs,
                    COALESCE(SUM(l.quantity * l.unit_price), 0) AS subtotal
             FROM {$docs_table} d
             INNER JOIN {$customers_table} c ON c.id = d.customer_id
             LEFT JOIN {$lines_table} l ON l.document_id = d.id
             WHERE d.status = %s
               AND d.issue_date >= %s
               AND d.issue_date < %s
             GROUP BY c.id, c.name, c.nuit
             ORDER BY subtotal DESC
             LIMIT %d",
            'issued',
            $start_date,
            $end_date,
            $limit
        );

        $rows = $wpdb->get_results( $sql );
        if ( empty( $rows ) ) {
            return array();
        }

        $items = array();
        foreach ( $rows as $row ) {
            $subtotal = (float) $row->subtotal;
            $items[]  = array(
                'customer_id'   => (int) $row->customer_id,
                'customer_name' => (string) $row->customer_name,
                'customer_nuit' => (string) $row->customer_nuit,
                'docs'          => (int) $row->docs,
                'subtotal'      => $subtotal,
                'total'         => $subtotal + ( $subtotal * $tax_rate ),
            );
        }

        return $items;
    }

    // Documentos recentes para quick action.
    private static function recent_documents( $limit ) {
        global $wpdb;

        $docs_table      = TPS_Documents_Model::table();
        $customers_table = TPS_Customers_Model::table();
        $limit           = max( 1, (int) $limit );

        $sql = $wpdb->prepare(
            "SELECT d.id, d.type, d.number, d.status, d.issue_date, d.created_at, c.name AS customer_name
             FROM {$docs_table} d
             LEFT JOIN {$customers_table} c ON c.id = d.customer_id
             ORDER BY d.created_at DESC
             LIMIT %d",
            $limit
        );

        $rows = $wpdb->get_results( $sql );
        if ( empty( $rows ) ) {
            return array();
        }

        $types = TPS_Documents_Model::types();
        $items = array();
        foreach ( $rows as $row ) {
            $doc_type = (string) $row->type;
            $items[]  = array(
                'id'            => (int) $row->id,
                'number'        => (int) $row->number,
                'type'          => $doc_type,
                'type_label'    => isset( $types[ $doc_type ] ) ? $types[ $doc_type ] : strtoupper( $doc_type ),
                'status'        => (string) $row->status,
                'issue_date'    => (string) $row->issue_date,
                'created_at'    => (string) $row->created_at,
                'customer_name' => (string) $row->customer_name,
                'edit_url'      => admin_url( 'admin.php?page=tps-documents-add&document_id=' . (int) $row->id ),
            );
        }

        return $items;
    }

    // Total em aberto de documentos emitidos.
    private static function receivable_total() {
        global $wpdb;

        $table = TPS_Documents_Model::table();
        $sql   = $wpdb->prepare(
            "SELECT COALESCE(SUM(balance_due), 0) FROM {$table} WHERE status = %s AND balance_due > 0",
            'issued'
        );

        return (float) $wpdb->get_var( $sql );
    }

    // Total vencido com saldo pendente.
    private static function overdue_total() {
        global $wpdb;

        $table = TPS_Documents_Model::table();
        $today = wp_date( 'Y-m-d' );
        $sql   = $wpdb->prepare(
            "SELECT COALESCE(SUM(balance_due), 0)
             FROM {$table}
             WHERE status = %s
               AND balance_due > 0
               AND due_date IS NOT NULL
               AND due_date < %s",
            'issued',
            $today
        );

        return (float) $wpdb->get_var( $sql );
    }

    // Produtos com stock abaixo do minimo.
    private static function critical_products( $limit ) {
        $limit = max( 1, (int) $limit );

        if ( ! class_exists( 'TPS_Inventory_Model' ) ) {
            return array();
        }

        $items = TPS_Inventory_Model::get_stock_items(
            array(
                'critical_only' => true,
                'orderby'       => 'stock_qty',
                'order'         => 'ASC',
                'per_page'      => $limit,
                'offset'        => 0,
            )
        );

        if ( empty( $items ) ) {
            return array();
        }

        $rows = array();
        foreach ( $items as $item ) {
            $rows[] = array(
                'id'         => (int) $item->id,
                'name'       => (string) $item->name,
                'sku'        => (string) ( $item->sku ?? '' ),
                'stock_qty'  => (float) $item->stock_qty,
                'min_stock'  => (float) $item->min_stock,
                'cost_price' => (float) $item->cost_price,
            );
        }

        return $rows;
    }
}
