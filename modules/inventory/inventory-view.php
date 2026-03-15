<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$items          = TPS_Inventory_Model::get_stock_items(
    array(
        'per_page' => 200,
        'offset'   => 0,
        'orderby'  => 'stock_qty',
        'order'    => 'ASC',
    )
);
$critical_count = TPS_Products_Services_Model::count_critical_products();
$tracked_total  = TPS_Products_Services_Model::count_items( array( 'type' => 'product', 'track_stock' => 1 ) );
$normal_count   = max( 0, $tracked_total - $critical_count );
?>

<div class="wrap tps-products-modern tps-inventory-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div>
                <h1>Stock</h1>
                <p class="tps-subtitle">Controle de saldo, custo medio e alertas de stock minimo com o mesmo padrao visual do sistema.</p>
            </div>
            <div class="tps-actions">
                <a href="<?php echo esc_url( tps_get_page_url( 'tps-stock-movements' ) ); ?>" class="tps-btn tps-btn-secondary">Ver Movimentos</a>
            </div>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-products tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Controlados</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format( $tracked_total, 0, '.', ',' ) ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-yes-alt tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Normais</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format( $normal_count, 0, '.', ',' ) ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-warning tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Criticos</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format( $critical_count, 0, '.', ',' ) ); ?></p>
        </article>
    </section>

    <section class="tps-inventory-grid">
        <article class="tps-panel">
            <div class="tps-panel-head">
                <div>
                    <h2 class="tps-panel-title">Novo Movimento</h2>
                    <p class="tps-panel-subtitle">Use entrada, saida manual ou ajuste para corrigir o saldo real.</p>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url( tps_get_action_url() ); ?>" class="tps-inventory-form">
                <?php wp_nonce_field( 'tps_save_inventory_movement' ); ?>
                <input type="hidden" name="action" value="tps_save_inventory_movement">

                <div class="tps-grid">
                    <div>
                        <label for="tps-inventory-product-search">Produto</label>
                        <div class="tps-search-wrap">
                            <input
                                id="tps-inventory-product-search"
                                class="tps-input"
                                type="text"
                                autocomplete="off"
                                placeholder="Pesquisar por nome ou SKU"
                                <?php disabled( empty( $items ) ); ?>
                            >
                            <input id="tps-inventory-product" type="hidden" name="product_id" required>
                            <div id="tps-inventory-product-results" class="tps-search-results" hidden></div>
                        </div>
                        <?php if ( empty( $items ) ) : ?>
                            <p class="tps-inline-meta">Nenhum produto com controlo de stock disponivel.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="tps-inventory-type">Tipo</label>
                        <select id="tps-inventory-type" class="tps-select" name="movement_type" required>
                            <option value="in">Entrada Manual</option>
                            <option value="out">Saida Manual</option>
                            <option value="adjustment">Ajuste de Inventario</option>
                        </select>
                    </div>
                    <div>
                        <label for="tps-inventory-quantity">Quantidade</label>
                        <input id="tps-inventory-quantity" class="tps-input" type="number" step="0.01" min="0.01" name="quantity" value="1">
                    </div>
                    <div>
                        <label for="tps-inventory-target">Saldo Final</label>
                        <input id="tps-inventory-target" class="tps-input" type="number" step="0.01" min="0" name="target_qty" value="0">
                    </div>
                    <div>
                        <label for="tps-inventory-cost">Custo Unitario</label>
                        <input id="tps-inventory-cost" class="tps-input" type="number" step="0.01" min="0" name="unit_cost" value="0.00">
                    </div>
                    <div>
                        <label for="tps-inventory-date">Data</label>
                        <input id="tps-inventory-date" class="tps-input" type="datetime-local" name="movement_date" value="<?php echo esc_attr( wp_date( 'Y-m-d\TH:i' ) ); ?>">
                    </div>
                    <div class="tps-field-full">
                        <label for="tps-inventory-notes">Notas</label>
                        <textarea id="tps-inventory-notes" class="tps-textarea" name="notes" rows="3" placeholder="Ex.: contagem fisica, compra local, devolucao do cliente"></textarea>
                    </div>
                </div>

                <div class="tps-actions tps-mt-14">
                    <?php submit_button( 'Registar Movimento', 'primary', 'submit', false ); ?>
                </div>
            </form>
        </article>

        <aside class="tps-panel tps-panel-soft">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-info-outline tps-icon" aria-hidden="true"></span>Regras de Stock</h2>
            <ul class="tps-inventory-notes">
                <li>Entrada manual aumenta o saldo e recalcula o custo medio.</li>
                <li>Saida manual reduz o saldo com base no custo medio actual.</li>
                <li>Ajuste de inventario define um novo saldo final apos contagem fisica.</li>
                <li>Produtos criticos sao os que estao com saldo igual ou abaixo do stock minimo.</li>
            </ul>
        </aside>
    </section>

    <section class="tps-table-shell">
        <div class="tps-section-head">
            <div>
                <h2 class="tps-section-title"><span class="dashicons dashicons-list-view tps-icon" aria-hidden="true"></span>Saldos Actuais</h2>
                <p class="tps-section-subtitle">Vista rapida dos produtos com controlo de stock activo.</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>SKU</th>
                    <th>Saldo Actual</th>
                    <th>Stock Minimo</th>
                    <th>Custo Medio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $items ) ) : ?>
                    <tr>
                        <td colspan="6">Nenhum produto com controlo de stock.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $items as $item ) : ?>
                        <?php $critical = (float) $item->stock_qty <= (float) $item->min_stock; ?>
                        <tr class="<?php echo esc_attr( $critical ? 'is-critical' : '' ); ?>">
                            <td>
                                <span class="tps-cell-with-icon">
                                    <span class="dashicons dashicons-products" aria-hidden="true"></span>
                                    <strong><?php echo esc_html( (string) $item->name ); ?></strong>
                                </span>
                            </td>
                            <td><?php echo esc_html( (string) ( $item->sku ?: '-' ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $item->stock_qty, 2 ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $item->min_stock, 2 ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $item->cost_price, 2 ) ); ?></td>
                            <td>
                                <span class="tps-badge <?php echo esc_attr( $critical ? 'tps-badge-cancelled' : 'tps-badge-issued' ); ?>">
                                    <?php echo esc_html( $critical ? 'Critico' : 'Normal' ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
