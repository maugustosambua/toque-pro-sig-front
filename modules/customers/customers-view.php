<?php // modules/customers/customers-view.php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : 0;
$customer = $id ? TPS_Customers_Model::get($id) : null;
$page_title = $id ? 'Editar Cliente' : 'Adicionar Cliente';
$page_subtitle = $id ? 'Atualize o perfil do cliente e os dados de contacto.' : 'Crie um novo perfil de cliente para as suas operações.';
$back_url = admin_url('admin.php?page=tps-customers');
?>

<style>
    .tps-customer-form {
        --tps-bg-soft: #f4f7fb;
        --tps-panel: #ffffff;
        --tps-text: #1a2433;
        --tps-text-muted: #5f6b7a;
        --tps-border: #dde5ef;
        --tps-accent: #0f5ea8;
        margin-top: 14px;
    }
    .tps-customer-form .tps-shell {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 14px;
        overflow: hidden;
    }
    .tps-customer-form .tps-header {
        background: linear-gradient(115deg, #f8fbff 0%, #edf4fc 55%, #e4edf8 100%);
        padding: 18px 20px;
        border-bottom: 1px solid var(--tps-border);
    }
    .tps-customer-form .tps-header-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tps-customer-form .tps-header-content {
        min-width: 0;
    }
    .tps-customer-form h1 {
        margin: 0;
        color: var(--tps-text);
        font-size: 24px;
        line-height: 1.2;
    }
    .tps-customer-form .tps-subtitle {
        margin: 6px 0 0;
        color: var(--tps-text-muted);
    }
    .tps-customer-form .tps-icon {
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        margin-right: 4px;
    }
    .tps-customer-form .tps-back-btn {
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
    .tps-customer-form .tps-back-btn:hover { border-color: #b8cbe3; background: #f6faff; color: #123f67; transform: translateY(-1px); }
    .tps-customer-form .tps-back-btn:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-customer-form .tps-back-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .tps-customer-form .tps-body {
        padding: 18px 20px 8px;
    }
    .tps-customer-form .tps-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 14px 18px;
    }
    .tps-customer-form .tps-field-full {
        grid-column: 1 / -1;
    }
    .tps-customer-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--tps-text);
    }
    .tps-customer-form input[type="text"],
    .tps-customer-form input[type="email"],
    .tps-customer-form select,
    .tps-customer-form textarea {
        width: 100%;
        min-height: 38px;
        border: 1px solid #ccd7e3;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
    }
    .tps-customer-form textarea {
        min-height: 92px;
        resize: vertical;
    }
    .tps-customer-form .tps-actions {
        border-top: 1px solid var(--tps-border);
        padding: 14px 20px 18px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .tps-customer-form .button-primary {
        border-radius: 8px;
        min-height: 36px;
        padding: 0 14px;
        transition: all .16s ease;
    }
    .tps-customer-form .button-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(26, 36, 51, .10); }
    .tps-customer-form .button-primary:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    @media (max-width: 782px) {
        .tps-customer-form .tps-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap tps-customer-form">
    <div class="tps-shell">
        <header class="tps-header">
            <div class="tps-header-row">
                <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                    <span>Voltar</span>
                </a>
                <div class="tps-header-content">
                    <h1><span class="dashicons dashicons-id tps-icon" aria-hidden="true"></span><?php echo esc_html($page_title); ?></h1>
                    <p class="tps-subtitle"><?php echo esc_html($page_subtitle); ?></p>
                </div>
            </div>
        </header>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="tps_save_customer">
            <?php wp_nonce_field('tps_save_customer'); ?>

            <?php if ($id) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
            <?php endif; ?>

            <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-type">Tipo</label>
                        <select id="tps-type" name="type" required>
                            <option value="individual" <?php selected($customer->type ?? '', 'individual'); ?>>Particular</option>
                            <option value="company" <?php selected($customer->type ?? '', 'company'); ?>>Empresa</option>
                        </select>
                    </div>

                    <div>
                        <label for="tps-name">Nome</label>
                        <input id="tps-name" type="text" name="name" required value="<?php echo esc_attr($customer->name ?? ''); ?>">
                    </div>

                    <div>
                        <label for="tps-nuit">NUIT</label>
                        <input id="tps-nuit" type="text" name="nuit" value="<?php echo esc_attr($customer->nuit ?? ''); ?>">
                    </div>

                    <div>
                        <label for="tps-email">Email</label>
                        <input id="tps-email" type="email" name="email" value="<?php echo esc_attr($customer->email ?? ''); ?>">
                    </div>

                    <div>
                        <label for="tps-phone">Telefone</label>
                        <input id="tps-phone" type="text" name="phone" value="<?php echo esc_attr($customer->phone ?? ''); ?>">
                    </div>

                    <div>
                        <label for="tps-city">Morada</label>
                        <input id="tps-city" type="text" name="city" value="<?php echo esc_attr($customer->city ?? ''); ?>">
                    </div>

                    <div class="tps-field-full">
                        <label for="tps-address">Endereço</label>
                        <textarea id="tps-address" name="address"><?php echo esc_textarea($customer->address ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tps-actions">
                <?php submit_button($id ? 'Atualizar Cliente' : 'Criar Cliente', 'primary', 'submit', false); ?>
            </div>
        </form>
    </div>
</div>
