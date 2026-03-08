<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id         = isset( $_GET['ps_id'] ) ? (int) $_GET['ps_id'] : 0;
$item       = $id ? TPS_Products_Services_Model::get( $id ) : null;
$page_title = $id ? 'Editar Produto/Servico' : 'Adicionar Produto/Servico';
$back_url   = admin_url( 'admin.php?page=tps-products-services' );
$is_product = ! $item || 'product' === $item->type;
?>

<div class="wrap tps-product-form">
    <div class="tps-shell">
        <header class="tps-header">
            <div class="tps-header-row">
                <a class="tps-back-btn" href="<?php echo esc_url( $back_url ); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                    <span>Voltar</span>
                </a>
                <div class="tps-header-content">
                    <h1><span class="dashicons dashicons-cart tps-icon" aria-hidden="true"></span><?php echo esc_html( $page_title ); ?></h1>
                    <p class="tps-subtitle">Cadastre itens para reutilizar nos documentos e controlar stock quando aplicavel.</p>
                </div>
            </div>
        </header>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="tps_save_product_service">
            <?php wp_nonce_field( 'tps_save_product_service' ); ?>

            <?php if ( $id ) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
            <?php endif; ?>

            <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-ps-type">Tipo</label>
                        <select id="tps-ps-type" name="type" required>
                            <option value="product" <?php selected( $item->type ?? '', 'product' ); ?>>Produto</option>
                            <option value="service" <?php selected( $item->type ?? '', 'service' ); ?>>Servico</option>
                        </select>
                    </div>

                    <div>
                        <label for="tps-ps-name">Nome</label>
                        <input id="tps-ps-name" type="text" name="name" required value="<?php echo esc_attr( $item->name ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-sku">Codigo (SKU)</label>
                        <input id="tps-ps-sku" type="text" name="sku" value="<?php echo esc_attr( $item->sku ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-unit">Unidade</label>
                        <input id="tps-ps-unit" type="text" name="unit" placeholder="ex.: un, hora, kg" value="<?php echo esc_attr( $item->unit ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-price">Preco de Venda</label>
                        <input id="tps-ps-price" type="number" name="price" min="0" step="0.01" required value="<?php echo esc_attr( isset( $item->price ) ? (string) $item->price : '0.00' ); ?>">
                    </div>

                    <div class="tps-stock-toggle">
                        <label for="tps-ps-track-stock">Controlar Stock</label>
                        <label>
                            <input id="tps-ps-track-stock" type="checkbox" name="track_stock" value="1" <?php checked( ! empty( $item->track_stock ) || ( ! $item && $is_product ) ); ?>>
                            Atualizar saldo automaticamente
                        </label>
                    </div>

                    <div class="tps-stock-field">
                        <label for="tps-ps-stock-qty">Saldo Inicial</label>
                        <input id="tps-ps-stock-qty" type="number" name="stock_qty" min="0" step="0.01" value="<?php echo esc_attr( isset( $item->stock_qty ) ? (string) $item->stock_qty : '0.00' ); ?>">
                    </div>

                    <div class="tps-stock-field">
                        <label for="tps-ps-min-stock">Stock Minimo</label>
                        <input id="tps-ps-min-stock" type="number" name="min_stock" min="0" step="0.01" value="<?php echo esc_attr( isset( $item->min_stock ) ? (string) $item->min_stock : '0.00' ); ?>">
                    </div>

                    <div class="tps-stock-field">
                        <label for="tps-ps-cost-price">Custo Medio Inicial</label>
                        <input id="tps-ps-cost-price" type="number" name="cost_price" min="0" step="0.01" value="<?php echo esc_attr( isset( $item->cost_price ) ? (string) $item->cost_price : '0.00' ); ?>">
                    </div>

                    <div class="tps-field-full">
                        <label for="tps-ps-description">Descricao</label>
                        <textarea id="tps-ps-description" name="description"><?php echo esc_textarea( $item->description ?? '' ); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tps-actions">
                <?php submit_button( $id ? 'Atualizar Item' : 'Criar Item', 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
</div>
