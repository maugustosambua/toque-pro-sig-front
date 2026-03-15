<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$search          = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$movement_type   = isset( $_GET['movement_type'] ) ? sanitize_key( wp_unslash( $_GET['movement_type'] ) ) : '';
$per_page        = max( 20, (int) tps_get_per_page() );
$movements       = TPS_Inventory_Model::get_movements(
    array(
        'search'        => $search,
        'movement_type' => $movement_type,
        'per_page'      => $per_page,
        'offset'        => 0,
    )
);
$types           = TPS_Inventory_Model::movement_types();
$total_movements = TPS_Inventory_Model::count_movements(
    array(
        'search'        => $search,
        'movement_type' => $movement_type,
    )
);
?>

<div class="wrap tps-documents-modern tps-inventory-movements-modern">
    <section class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url( tps_get_page_url( 'tps-inventory' ) ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1>Movimentos</h1>
                <p class="tps-subtitle">Historico de entradas, saidas, ajustes e baixas automaticas integradas com documentos.</p>
            </div>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-database-view tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Registos</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format( $total_movements, 0, '.', ',' ) ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-filter tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Filtro</span></p>
            <p class="tps-stat-value"><?php echo esc_html( '' !== $movement_type ? ( $types[ $movement_type ] ?? $movement_type ) : 'Todos' ); ?></p>
        </article>
    </section>

    <section class="tps-toolbar tps-inventory-toolbar">
        <form method="get" class="tps-toolbar-form">
            <input type="hidden" name="tps_view" value="tps-stock-movements">
            <div><input class="tps-search tps-input" type="search" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Pesquisar por produto, SKU ou referencia"></div>
            <div><select class="tps-select" name="movement_type">
                <option value="">Todos os tipos</option>
                <?php foreach ( $types as $type_key => $type_label ) : ?>
                    <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $movement_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><button class="tps-btn tps-btn-secondary w-100" type="submit">Filtrar</button></div>
        </form>
    </section>

    <section class="tps-table-shell">
        <div class="tps-section-head">
            <div>
                <h2 class="tps-section-title"><span class="dashicons dashicons-editor-table tps-icon" aria-hidden="true"></span>Historico</h2>
                <p class="tps-section-subtitle">Cada registo mostra o impacto no stock e a origem do movimento.</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Custo</th>
                    <th>Referencia</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $movements ) ) : ?>
                    <tr>
                        <td colspan="7">Sem movimentos registados.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $movements as $movement ) : ?>
                        <?php
                        $badge_class = 'tps-badge-draft';
                        if ( 'in' === $movement->movement_type ) {
                            $badge_class = 'tps-badge-issued';
                        } elseif ( 'out' === $movement->movement_type ) {
                            $badge_class = 'tps-badge-cancelled';
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( (string) $movement->movement_date ); ?></td>
                            <td>
                                <span class="tps-cell-with-icon">
                                    <span class="dashicons dashicons-products" aria-hidden="true"></span>
                                    <strong><?php echo esc_html( (string) $movement->product_name ); ?></strong>
                                </span>
                                <?php if ( ! empty( $movement->product_sku ) ) : ?>
                                    <div class="tps-inline-meta"><?php echo esc_html( (string) $movement->product_sku ); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="tps-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( (string) ( $types[ $movement->movement_type ] ?? $movement->movement_type ) ); ?></span></td>
                            <td><?php echo esc_html( number_format( (float) $movement->quantity, 2 ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $movement->unit_cost, 2 ) ); ?></td>
                            <td><?php echo esc_html( (string) $movement->reference_type . ( ! empty( $movement->reference_id ) ? ' #' . (int) $movement->reference_id : '' ) ); ?></td>
                            <td><?php echo esc_html( (string) ( $movement->notes ?: '-' ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
