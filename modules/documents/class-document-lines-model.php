<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Document_Lines_Model {

    // Nome da tabela
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_document_lines';
    }

    // Insere ou actualiza linha
    public static function insert( $data ) {
        global $wpdb;

        // Procura linha existente
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                WHERE document_id = %d AND (
                    ( product_service_id IS NOT NULL AND product_service_id = %d )
                    OR
                    ( ( product_service_id IS NULL OR product_service_id = 0 ) AND description = %s )
                )",
                $data['document_id'],
                isset( $data['product_service_id'] ) ? (int) $data['product_service_id'] : 0,
                $data['description']
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
                ),
                array( 'id' => $existing->id ),
                array( '%f', '%f' ),
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
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%f',
                '%f',
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

        $subtotal = self::document_total( $document_id );
        $iva      = tps_calculate_iva( $subtotal );

        return $subtotal + $iva;
    }

    // Verifica se documento tem linhas
    public static function has_lines( $document_id ) {

        $lines = self::get_by_document( $document_id );
        return ! empty( $lines );
    }

    // Retorna subtotal, iva e total de um documento
    public static function totals( $document_id ) {

        $subtotal = self::document_total( $document_id );
        $iva      = tps_calculate_iva( $subtotal );
        $total    = $subtotal + $iva;

        return array(
            'subtotal' => $subtotal,
            'iva'      => $iva,
            'total'    => $total,
        );
    }
    

}
