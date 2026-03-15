<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id         = isset( $_GET['ps_id'] ) ? (int) $_GET['ps_id'] : 0;
$item       = $id ? TPS_Products_Services_Model::get( $id ) : null;
$page_title = $id ? 'Editar Produto/Servico' : 'Adicionar Produto/Servico';
$back_url   = tps_get_page_url( 'tps-products-services' );
$is_product = ! $item || 'product' === $item->type;
?>

<div class="wrap tps-product-form">
    <header class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url( $back_url ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1><?php echo esc_html( $page_title ); ?></h1>
                <p class="tps-subtitle">Cadastre itens para reutilizar nos documentos e controlar stock quando aplicavel.</p>
            </div>
        </div>
    </header>

    <div class="tps-shell">
        <form method="post" action="<?php echo esc_url( tps_get_action_url() ); ?>">
            <input type="hidden" name="action" value="tps_save_product_service">
            <?php wp_nonce_field( 'tps_save_product_service' ); ?>

            <?php if ( $id ) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
            <?php endif; ?>

            <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-ps-type">Tipo</label>
                        <select id="tps-ps-type" class="tps-select" name="type" required>
                            <option value="product" <?php selected( $item->type ?? '', 'product' ); ?>>Produto</option>
                            <option value="service" <?php selected( $item->type ?? '', 'service' ); ?>>Servico</option>
                        </select>
                    </div>

                    <div>
                        <label for="tps-ps-name">Nome</label>
                        <input id="tps-ps-name" class="tps-input" type="text" name="name" required value="<?php echo esc_attr( $item->name ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-sku">Codigo (SKU)</label>
                        <input id="tps-ps-sku" class="tps-input" type="text" name="sku" value="<?php echo esc_attr( $item->sku ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-unit">Unidade</label>
                        <input id="tps-ps-unit" class="tps-input" type="text" name="unit" placeholder="ex.: un, hora, kg" value="<?php echo esc_attr( $item->unit ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-price">Preco de Venda</label>
                        <input id="tps-ps-price" class="tps-input" type="number" name="price" min="0" step="0.01" required value="<?php echo esc_attr( isset( $item->price ) ? (string) $item->price : '0.00' ); ?>">
                    </div>

                    <div class="tps-stock-toggle">
                        <span class="tps-field-label">Controlar Stock</span>
                        <label class="tps-stock-toggle-card" for="tps-ps-track-stock">
                            <span class="tps-stock-toggle-copy">
                                <span class="tps-stock-toggle-title">Atualizar saldo automaticamente</span>
                                <span class="tps-stock-toggle-help">Ative apenas para produtos fisicos com controlo de inventario.</span>
                            </span>
                            <span class="tps-stock-toggle-control">
                                <input id="tps-ps-track-stock" type="checkbox" name="track_stock" value="1" <?php checked( ! empty( $item->track_stock ) || ( ! $item && $is_product ) ); ?>>
                                <span class="tps-stock-toggle-slider" aria-hidden="true"></span>
                            </span>
                        </label>
                    </div>

                    <div class="tps-stock-field">
                        <label for="tps-ps-stock-qty">Saldo Inicial</label>
                        <input id="tps-ps-stock-qty" class="tps-input" type="number" name="stock_qty" min="0" step="0.01" value="<?php echo esc_attr( isset( $item->stock_qty ) ? (string) $item->stock_qty : '0.00' ); ?>">
                    </div>

                    <div class="tps-stock-field">
                        <label for="tps-ps-min-stock">Stock Minimo</label>
                        <input id="tps-ps-min-stock" class="tps-input" type="number" name="min_stock" min="0" step="0.01" value="<?php echo esc_attr( isset( $item->min_stock ) ? (string) $item->min_stock : '0.00' ); ?>">
                    </div>

                    <div class="tps-stock-field">
                        <label for="tps-ps-cost-price">Custo Medio Inicial</label>
                        <input id="tps-ps-cost-price" class="tps-input" type="number" name="cost_price" min="0" step="0.01" value="<?php echo esc_attr( isset( $item->cost_price ) ? (string) $item->cost_price : '0.00' ); ?>">
                    </div>

                    <div class="tps-field-full">
                        <label for="tps-ps-description">Descricao</label>
                        <textarea id="tps-ps-description" class="tps-textarea" name="description"><?php echo esc_textarea( $item->description ?? '' ); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tps-actions">
                <?php submit_button( $id ? 'Atualizar Item' : 'Criar Item', 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
</div>
