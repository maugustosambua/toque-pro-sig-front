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
                WHERE document_id = %d AND description = %s",
                $data['document_id'],
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
                'description' => $data['description'],
                'quantity'    => $data['quantity'],
                'unit_price'  => $data['unit_price'],
            ),
            array(
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
                "SELECT * FROM " . self::table() . " WHERE document_id = %d",
                $document_id
            )
        );
    }

    // Remove linhas de um documento
    public static function delete_by_document( $document_id ) {
        global $wpdb;

        return $wpdb->delete(
            self::table(),
            array( 'document_id' => (int) $document_id ),
            array( '%d' )
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
