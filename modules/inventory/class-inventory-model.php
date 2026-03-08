<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Inventory_Model {

    // Nome da tabela de movimentos.
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_stock_movements';
    }

    // Tipos de movimento suportados.
    public static function movement_types() {
        return array(
            'in'         => 'Entrada',
            'out'        => 'Saida',
            'adjustment' => 'Ajuste',
        );
    }

    // Calcula o saldo actual de um produto.
    public static function get_current_balance( $product_id ) {
        $product = TPS_Products_Services_Model::get( $product_id );
        if ( ! $product ) {
            return 0.0;
        }

        return (float) $product->stock_qty;
    }

    // Retorna custo medio actual.
    public static function get_average_cost( $product_id ) {
        $product = TPS_Products_Services_Model::get( $product_id );
        if ( ! $product ) {
            return 0.0;
        }

        return (float) $product->cost_price;
    }

    // Regista um movimento e actualiza saldo/custo medio no produto.
    public static function create_movement( $data ) {
        global $wpdb;

        $product_id     = isset( $data['product_id'] ) ? (int) $data['product_id'] : 0;
        $movement_type  = isset( $data['movement_type'] ) ? sanitize_key( $data['movement_type'] ) : '';
        $quantity       = isset( $data['quantity'] ) ? (float) $data['quantity'] : 0.0;
        $reference_type = isset( $data['reference_type'] ) ? sanitize_key( $data['reference_type'] ) : '';
        $reference_id   = isset( $data['reference_id'] ) ? (int) $data['reference_id'] : 0;
        $notes          = isset( $data['notes'] ) ? sanitize_textarea_field( (string) $data['notes'] ) : '';
        $movement_date  = ! empty( $data['movement_date'] ) ? (string) $data['movement_date'] : current_time( 'mysql' );

        if ( $product_id <= 0 || $quantity <= 0 || ! isset( self::movement_types()[ $movement_type ] ) ) {
            return new WP_Error( 'invalid_inventory_movement', 'Dados invalidos para movimento de stock.' );
        }

        $product = TPS_Products_Services_Model::get( $product_id );
        if ( ! $product || 'product' !== $product->type || (int) $product->track_stock !== 1 ) {
            return new WP_Error( 'invalid_inventory_product', 'Produto invalido para controlo de stock.' );
        }

        $current_qty  = (float) $product->stock_qty;
        $current_cost = (float) $product->cost_price;
        $unit_cost    = isset( $data['unit_cost'] ) ? max( 0.0, (float) $data['unit_cost'] ) : $current_cost;
        $new_qty      = $current_qty;
        $new_cost     = $current_cost;

        if ( 'in' === $movement_type ) {
            $effective_cost = $unit_cost > 0 ? $unit_cost : $current_cost;
            $new_qty        = $current_qty + $quantity;
            $new_cost       = $new_qty > 0 ? ( ( $current_qty * $current_cost ) + ( $quantity * $effective_cost ) ) / $new_qty : 0.0;
            $unit_cost      = $effective_cost;
        } elseif ( 'out' === $movement_type ) {
            if ( $quantity > $current_qty ) {
                return new WP_Error( 'insufficient_stock', 'Stock insuficiente para o movimento.' );
            }

            $unit_cost = $current_cost;
            $new_qty   = $current_qty - $quantity;
        } else {
            $target_qty = isset( $data['target_qty'] ) ? (float) $data['target_qty'] : $current_qty;
            $delta_qty  = $target_qty - $current_qty;

            if ( abs( $delta_qty ) < 0.00001 ) {
                return new WP_Error( 'no_adjustment_needed', 'O ajuste nao altera o saldo actual.' );
            }

            $quantity  = abs( $delta_qty );
            $new_qty   = $target_qty;
            $unit_cost = isset( $data['unit_cost'] ) ? max( 0.0, (float) $data['unit_cost'] ) : $current_cost;

            if ( $delta_qty > 0 ) {
                $effective_cost = $unit_cost > 0 ? $unit_cost : $current_cost;
                $new_cost       = $new_qty > 0 ? ( ( $current_qty * $current_cost ) + ( $quantity * $effective_cost ) ) / $new_qty : 0.0;
                $unit_cost      = $effective_cost;
            } else {
                $new_cost = $current_cost;
                $unit_cost = $current_cost;
            }
        }

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->insert(
            self::table(),
            array(
                'product_id'      => $product_id,
                'movement_type'   => $movement_type,
                'quantity'        => $quantity,
                'unit_cost'       => $unit_cost,
                'reference_type'  => $reference_type,
                'reference_id'    => $reference_id > 0 ? $reference_id : null,
                'movement_date'   => $movement_date,
                'notes'           => $notes,
                'created_at'      => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%f', '%f', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'inventory_insert_failed', 'Nao foi possivel gravar o movimento.' );
        }

        $updated = TPS_Products_Services_Model::update(
            $product_id,
            array(
                'type'        => $product->type,
                'name'        => $product->name,
                'sku'         => $product->sku,
                'unit'        => $product->unit,
                'price'       => (float) $product->price,
                'track_stock' => (int) $product->track_stock,
                'min_stock'   => (float) $product->min_stock,
                'stock_qty'   => $new_qty,
                'cost_price'  => $new_cost,
                'description' => $product->description,
            )
        );

        if ( false === $updated ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'inventory_product_update_failed', 'Nao foi possivel actualizar o saldo do produto.' );
        }

        $wpdb->query( 'COMMIT' );

        return true;
    }

    // Verifica se um documento ja gerou movimentos de stock.
    public static function document_has_stock_movements( $document_id, $reference_type = 'document' ) {
        global $wpdb;

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::table() . ' WHERE reference_type = %s AND reference_id = %d',
                $reference_type,
                (int) $document_id
            )
        );

        return $count > 0;
    }

    // Gera movimentos de saida a partir de um documento.
    public static function apply_document_issue( $document_id ) {
        $lines = TPS_Document_Lines_Model::get_by_document( $document_id );
        if ( empty( $lines ) ) {
            return true;
        }

        $required = array();
        foreach ( $lines as $line ) {
            $product_id = isset( $line->product_service_id ) ? (int) $line->product_service_id : 0;
            if ( $product_id <= 0 ) {
                continue;
            }

            $product = TPS_Products_Services_Model::get( $product_id );
            if ( ! $product || 'product' !== $product->type || (int) $product->track_stock !== 1 ) {
                continue;
            }

            if ( ! isset( $required[ $product_id ] ) ) {
                $required[ $product_id ] = 0.0;
            }

            $required[ $product_id ] += (float) $line->quantity;
        }

        foreach ( $required as $product_id => $qty_needed ) {
            $product = TPS_Products_Services_Model::get( $product_id );
            if ( ! $product ) {
                return new WP_Error( 'invalid_inventory_product', 'Produto invalido para controlo de stock.' );
            }

            if ( $qty_needed > (float) $product->stock_qty ) {
                return new WP_Error(
                    'insufficient_stock',
                    sprintf( 'Stock insuficiente para o produto %s.', $product->name )
                );
            }
        }

        foreach ( $lines as $line ) {
            $product_id = isset( $line->product_service_id ) ? (int) $line->product_service_id : 0;
            if ( $product_id <= 0 ) {
                continue;
            }

            $product = TPS_Products_Services_Model::get( $product_id );
            if ( ! $product || 'product' !== $product->type || (int) $product->track_stock !== 1 ) {
                continue;
            }

            $result = self::create_movement(
                array(
                    'product_id'     => $product_id,
                    'movement_type'  => 'out',
                    'quantity'       => (float) $line->quantity,
                    'unit_cost'      => (float) $product->cost_price,
                    'reference_type' => 'document',
                    'reference_id'   => (int) $document_id,
                    'movement_date'  => current_time( 'mysql' ),
                    'notes'          => 'Saida automatica por emissao de documento.',
                )
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        return true;
    }

    // Reverte movimentos de um documento cancelado.
    public static function reverse_document_issue( $document_id ) {
        $lines = TPS_Document_Lines_Model::get_by_document( $document_id );
        if ( empty( $lines ) ) {
            return true;
        }

        foreach ( $lines as $line ) {
            $product_id = isset( $line->product_service_id ) ? (int) $line->product_service_id : 0;
            if ( $product_id <= 0 ) {
                continue;
            }

            $product = TPS_Products_Services_Model::get( $product_id );
            if ( ! $product || 'product' !== $product->type || (int) $product->track_stock !== 1 ) {
                continue;
            }

            $result = self::create_movement(
                array(
                    'product_id'     => $product_id,
                    'movement_type'  => 'in',
                    'quantity'       => (float) $line->quantity,
                    'unit_cost'      => self::get_document_issue_cost( $document_id, $product_id, (float) $product->cost_price ),
                    'reference_type' => 'document_cancel',
                    'reference_id'   => (int) $document_id,
                    'movement_date'  => current_time( 'mysql' ),
                    'notes'          => 'Reposicao automatica por cancelamento do documento.',
                )
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        return true;
    }

    // Retorna custo usado na saida automatica do documento.
    private static function get_document_issue_cost( $document_id, $product_id, $fallback_cost ) {
        global $wpdb;

        $cost = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT unit_cost FROM ' . self::table() . ' WHERE reference_type = %s AND reference_id = %d AND product_id = %d ORDER BY id DESC LIMIT 1',
                'document',
                (int) $document_id,
                (int) $product_id
            )
        );

        if ( null === $cost ) {
            return (float) $fallback_cost;
        }

        return (float) $cost;
    }

    // Lista movimentos.
    public static function get_movements( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'search'        => '',
            'movement_type' => '',
            'product_id'    => 0,
            'per_page'      => 50,
            'offset'        => 0,
        );

        $args  = wp_parse_args( $args, $defaults );
        $where = array();

        if ( ! empty( $args['movement_type'] ) && isset( self::movement_types()[ $args['movement_type'] ] ) ) {
            $where[] = $wpdb->prepare( 'm.movement_type = %s', $args['movement_type'] );
        }

        if ( ! empty( $args['product_id'] ) ) {
            $where[] = $wpdb->prepare( 'm.product_id = %d', (int) $args['product_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = $wpdb->prepare( '(p.name LIKE %s OR p.sku LIKE %s OR m.reference_type LIKE %s)', $like, $like, $like );
        }

        $sql = 'SELECT m.*, p.name AS product_name, p.sku AS product_sku, p.unit AS product_unit
            FROM ' . self::table() . ' m
            INNER JOIN ' . TPS_Products_Services_Model::table() . ' p ON p.id = m.product_id';

        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        $sql .= ' ORDER BY m.movement_date DESC, m.id DESC';
        $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', (int) $args['per_page'], (int) $args['offset'] );

        return $wpdb->get_results( $sql );
    }

    // Conta movimentos.
    public static function count_movements( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'search'        => '',
            'movement_type' => '',
            'product_id'    => 0,
        );

        $args  = wp_parse_args( $args, $defaults );
        $where = array();
        $join  = '';

        if ( ! empty( $args['movement_type'] ) && isset( self::movement_types()[ $args['movement_type'] ] ) ) {
            $where[] = $wpdb->prepare( 'm.movement_type = %s', $args['movement_type'] );
        }

        if ( ! empty( $args['product_id'] ) ) {
            $where[] = $wpdb->prepare( 'm.product_id = %d', (int) $args['product_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $join    = ' INNER JOIN ' . TPS_Products_Services_Model::table() . ' p ON p.id = m.product_id';
            $where[] = $wpdb->prepare( '(p.name LIKE %s OR p.sku LIKE %s OR m.reference_type LIKE %s)', $like, $like, $like );
        }

        $sql = 'SELECT COUNT(*) FROM ' . self::table() . ' m' . $join;
        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        return (int) $wpdb->get_var( $sql );
    }

    // Retorna produtos com stock controlado.
    public static function get_stock_items( $args = array() ) {
        return TPS_Products_Services_Model::get_items(
            wp_parse_args(
                $args,
                array(
                    'type'        => 'product',
                    'track_stock' => 1,
                    'orderby'     => 'name',
                    'order'       => 'ASC',
                )
            )
        );
    }
}
