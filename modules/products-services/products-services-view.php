<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id         = isset( $_GET['ps_id'] ) ? (int) $_GET['ps_id'] : 0;
$item       = $id ? TPS_Products_Services_Model::get( $id ) : null;
$page_title = $id ? 'Editar Produto/Serviço' : 'Adicionar Produto/Serviço';
$back_url   = admin_url( 'admin.php?page=tps-products-services' );
?>

<style>
    .tps-product-form {
        --tps-panel: #ffffff;
        --tps-text: #1a2433;
        --tps-text-muted: #5f6b7a;
        --tps-border: #dde5ef;
        margin-top: 14px;
    }
    .tps-product-form .tps-shell {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 14px;
        overflow: hidden;
    }
    .tps-product-form .tps-header {
        background: linear-gradient(115deg, #f8fbff 0%, #edf4fc 55%, #e4edf8 100%);
        padding: 18px 20px;
        border-bottom: 1px solid var(--tps-border);
    }
    .tps-product-form .tps-header-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tps-product-form .tps-header-content {
        min-width: 0;
    }
    .tps-product-form h1 {
        margin: 0;
        color: var(--tps-text);
        font-size: 24px;
    }
    .tps-product-form .tps-icon {
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        margin-right: 4px;
    }
    .tps-product-form .tps-subtitle {
        margin: 6px 0 0;
        color: var(--tps-text-muted);
    }
    .tps-product-form .tps-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--tps-border);
        border-radius: 999px;
        padding: 6px 12px;
        text-decoration: none;
        color: var(--tps-text);
        background: #fff;
        font-weight: 600;
        line-height: 1;
        transition: all .16s ease;
    }
    .tps-product-form .tps-back-btn:hover { border-color: #b8cbe3; background: #f6faff; color: #123f67; transform: translateY(-1px); }
    .tps-product-form .tps-back-btn:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-product-form .tps-back-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .tps-product-form .tps-body {
        padding: 18px 20px 8px;
    }
    .tps-product-form .tps-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 14px 18px;
    }
    .tps-product-form .tps-field-full {
        grid-column: 1 / -1;
    }
    .tps-product-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--tps-text);
    }
    .tps-product-form input,
    .tps-product-form select,
    .tps-product-form textarea {
        width: 100%;
        min-height: 38px;
        border: 1px solid #ccd7e3;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
    }
    .tps-product-form textarea {
        min-height: 92px;
        resize: vertical;
    }
    .tps-product-form .tps-actions {
        border-top: 1px solid var(--tps-border);
        padding: 14px 20px 18px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .tps-product-form .button-primary {
        border-radius: 8px;
        min-height: 36px;
        padding: 0 14px;
        transition: all .16s ease;
    }
    .tps-product-form .button-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(26, 36, 51, .10); }
    .tps-product-form .button-primary:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    @media (max-width: 782px) {
        .tps-product-form .tps-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

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

