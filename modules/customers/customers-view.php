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
