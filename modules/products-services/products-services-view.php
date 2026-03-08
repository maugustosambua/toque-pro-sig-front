<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id         = isset( $_GET['ps_id'] ) ? (int) $_GET['ps_id'] : 0;
$item       = $id ? TPS_Products_Services_Model::get( $id ) : null;
$page_title = $id ? 'Editar Produto/Serviço' : 'Adicionar Produto/Serviço';
$back_url   = admin_url( 'admin.php?page=tps-products-services' );
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
                    <p class="tps-subtitle">Cadastre itens para reutilizar nos documentos.</p>
                </div>
            </div>
        </header>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="tps_save_product_service">
            <?php wp_nonce_field( 'tps_save_product_service' ); ?>

            <?php if ( $id ) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
            <?php endif; ?>

            <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-ps-type">Tipo</label>
                        <select id="tps-ps-type" name="type" required>
                            <option value="product" <?php selected( $item->type ?? '', 'product' ); ?>>Produto</option>
                            <option value="service" <?php selected( $item->type ?? '', 'service' ); ?>>Serviço</option>
                        </select>
                    </div>

                    <div>
                        <label for="tps-ps-name">Nome</label>
                        <input id="tps-ps-name" type="text" name="name" required value="<?php echo esc_attr( $item->name ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-sku">Código (SKU)</label>
                        <input id="tps-ps-sku" type="text" name="sku" value="<?php echo esc_attr( $item->sku ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-unit">Unidade</label>
                        <input id="tps-ps-unit" type="text" name="unit" placeholder="ex.: un, hora, kg" value="<?php echo esc_attr( $item->unit ?? '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-ps-price">Preço</label>
                        <input id="tps-ps-price" type="number" name="price" min="0" step="0.01" required value="<?php echo esc_attr( isset( $item->price ) ? (string) $item->price : '0.00' ); ?>">
                    </div>

                    <div class="tps-field-full">
                        <label for="tps-ps-description">Descrição</label>
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

