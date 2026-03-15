<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Document_Lines_Model {

    // Normaliza código de isenção fiscal.
    public static function normalize_exemption_code( $code ) {
        $code = strtoupper( trim( (string) $code ) );

        return preg_replace( '/[^A-Z0-9\-_.]/', '', $code );
    }

    // Modos fiscais por linha.
    public static function tax_modes() {
        return array(
            'taxable'     => 'Tributado',
            'exempt'      => 'Isento',
            'non_taxable' => 'Nao sujeito',
        );
    }

    // Nome da tabela
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_document_lines';
    }

    // Insere ou actualiza linha
    public static function insert( $data ) {
        global $wpdb;

        $document_id = isset( $data['document_id'] ) ? (int) $data['document_id'] : 0;
        if ( $document_id <= 0 ) {
            return false;
        }

        if ( class_exists( 'TPS_Documents_Model' ) ) {
            $document = TPS_Documents_Model::get( $document_id );
            if ( ! $document || 'draft' !== (string) $document->status ) {
                return false;
            }
        }

        // Procura linha existente
        $tax_mode         = isset( $data['tax_mode'] ) ? sanitize_key( (string) $data['tax_mode'] ) : 'taxable';
        $tax_rate         = isset( $data['tax_rate'] ) ? (float) $data['tax_rate'] : (float) ( tps_get_iva_rate() * 100 );
        $exemption_code   = isset( $data['exemption_code'] ) ? self::normalize_exemption_code( $data['exemption_code'] ) : '';
        $exemption_reason = isset( $data['exemption_reason'] ) ? sanitize_text_field( (string) $data['exemption_reason'] ) : '';

        if ( ! isset( self::tax_modes()[ $tax_mode ] ) ) {
            $tax_mode = 'taxable';
        }

        if ( 'taxable' === $tax_mode ) {
            $exemption_code   = '';
            $exemption_reason = '';
        }

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                WHERE document_id = %d AND (
                    ( product_service_id IS NOT NULL AND product_service_id = %d )
                    OR
                    ( ( product_service_id IS NULL OR product_service_id = 0 ) AND description = %s )
                )
                AND tax_mode = %s
                AND ROUND(tax_rate, 2) = ROUND(%f, 2)
                AND COALESCE(exemption_code, '') = %s
                AND COALESCE(exemption_reason, '') = %s",
                $document_id,
                isset( $data['product_service_id'] ) ? (int) $data['product_service_id'] : 0,
                $data['description'],
                $tax_mode,
                $tax_rate,
                $exemption_code,
                $exemption_reason
            )
        );

        // Se já existe, soma quantidade
        if ( $existing ) {

            $new_qty = $existing->quantity + $data['quantity'];

            return $wpdb->update(
                self::table(),
                array(
                    'quantity'   => $new_qty,
                    'unit_price' => $data['unit_price'],
                    'tax_mode'   => $tax_mode,
                    'tax_rate'   => $tax_rate,
                    'exemption_code' => $exemption_code,
                    'exemption_reason' => $exemption_reason,
                ),
                array( 'id' => $existing->id ),
                array( '%f', '%f', '%s', '%f', '%s', '%s' ),
                array( '%d' )
            );
        }

        // Se não existe, cria nova linha
        return $wpdb->insert(
            self::table(),
            array(
                'document_id' => $data['document_id'],
                'product_service_id' => ! empty( $data['product_service_id'] ) ? (int) $data['product_service_id'] : null,
                'description' => $data['description'],
                'quantity'    => $data['quantity'],
                'unit_price'  => $data['unit_price'],
                'tax_mode'    => $tax_mode,
                'tax_rate'    => $tax_rate,
                'exemption_code' => $exemption_code,
                'exemption_reason' => $exemption_reason,
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%f',
                '%f',
                '%s',
                '%f',
                '%s',
                '%s',
            )
        );
    }


    // Retorna linhas de um documento
    public static function get_by_document( $document_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, p.name AS item_name, p.sku AS item_sku, p.track_stock
                FROM " . self::table() . " l
                LEFT JOIN " . TPS_Products_Services_Model::table() . " p ON p.id = l.product_service_id
                WHERE l.document_id = %d",
                $document_id
            )
        );
    }

    // Calcula subtotal de uma linha
    public static function line_total( $line ) {
        return (float) $line->quantity * (float) $line->unit_price;
    }

    // Determina se linha é tributável para IVA.
    public static function is_taxable_line( $line ) {
        $tax_mode = isset( $line->tax_mode ) ? sanitize_key( (string) $line->tax_mode ) : 'taxable';

        return 'taxable' === $tax_mode;
    }

    // Calcula IVA da linha.
    public static function line_iva( $line ) {
        if ( ! self::is_taxable_line( $line ) ) {
            return 0.0;
        }

        $line_total = self::line_total( $line );
        $line_rate  = isset( $line->tax_rate ) ? (float) $line->tax_rate : (float) ( tps_get_iva_rate() * 100 );
        $line_rate  = max( 0, min( 100, $line_rate ) );

        return $line_total * ( $line_rate / 100 );
    }

    // Calcula total do documento
    public static function document_total( $document_id ) {

        $lines = self::get_by_document( $document_id );
        $total = 0;

        foreach ( $lines as $line ) {
            $total += self::line_total( $line );
        }

        return $total;
    }

    // Total do documento com IVA
    public static function document_total_with_tax( $document_id ) {
        $totals = self::totals( $document_id );

        return (float) $totals['total'];
    }

    // Verifica se documento tem linhas
    public static function has_lines( $document_id ) {

        $lines = self::get_by_document( $document_id );
        return ! empty( $lines );
    }

    // Retorna subtotal, iva e total de um documento
    public static function totals( $document_id ) {
        $lines    = self::get_by_document( $document_id );
        $subtotal = 0.0;
        $iva      = 0.0;

        foreach ( $lines as $line ) {
            $subtotal += self::line_total( $line );
            $iva      += self::line_iva( $line );
        }

        $total = $subtotal + $iva;

        return array(
            'subtotal' => $subtotal,
            'iva'      => $iva,
            'total'    => $total,
        );
    }

    // Copia linhas fiscais de um documento origem para outro (ex.: nota de ajuste).
    public static function clone_lines_to_document( $source_document_id, $target_document_id ) {
        global $wpdb;

        $source_document_id = (int) $source_document_id;
        $target_document_id = (int) $target_document_id;

        if ( $source_document_id <= 0 || $target_document_id <= 0 || $source_document_id === $target_document_id ) {
            return false;
        }

        if ( class_exists( 'TPS_Documents_Model' ) ) {
            $target_document = TPS_Documents_Model::get( $target_document_id );
            if ( ! $target_document || 'draft' !== (string) $target_document->status ) {
                return false;
            }
        }

        $source_lines = self::get_by_document( $source_document_id );
        if ( empty( $source_lines ) ) {
            return true;
        }

        foreach ( $source_lines as $line ) {
            $tax_mode = isset( $line->tax_mode ) ? sanitize_key( (string) $line->tax_mode ) : 'taxable';
            if ( ! isset( self::tax_modes()[ $tax_mode ] ) ) {
                $tax_mode = 'taxable';
            }

            $tax_rate         = isset( $line->tax_rate ) ? (float) $line->tax_rate : (float) ( tps_get_iva_rate() * 100 );
            $exemption_code   = isset( $line->exemption_code ) ? self::normalize_exemption_code( $line->exemption_code ) : '';
            $exemption_reason = isset( $line->exemption_reason ) ? sanitize_text_field( (string) $line->exemption_reason ) : '';

            if ( 'taxable' === $tax_mode ) {
                $exemption_code   = '';
                $exemption_reason = '';
            }

            $inserted = $wpdb->insert(
                self::table(),
                array(
                    'document_id'        => $target_document_id,
                    'product_service_id' => ! empty( $line->product_service_id ) ? (int) $line->product_service_id : null,
                    'description'        => (string) $line->description,
                    'quantity'           => (float) $line->quantity,
                    'unit_price'         => (float) $line->unit_price,
                    'tax_mode'           => $tax_mode,
                    'tax_rate'           => $tax_rate,
                    'exemption_code'     => $exemption_code,
                    'exemption_reason'   => $exemption_reason,
                ),
                array(
                    '%d',
                    '%d',
                    '%s',
                    '%f',
                    '%f',
                    '%s',
                    '%f',
                    '%s',
                    '%s',
                )
            );

            if ( false === $inserted ) {
                return false;
            }
        }

        return true;
    }
    

}
