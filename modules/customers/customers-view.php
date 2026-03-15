<?php // modules/customers/customers-view.php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : 0;
$customer = $id ? TPS_Customers_Model::get($id) : null;
$page_title = $id ? 'Editar Cliente' : 'Adicionar Cliente';
$page_subtitle = $id ? 'Atualize o perfil do cliente e os dados de contacto.' : 'Crie um novo perfil de cliente para as suas operações.';
$back_url = tps_get_page_url('tps-customers');
?>

<div class="wrap tps-customer-form">
    <header class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url( $back_url ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1><?php echo esc_html($page_title); ?></h1>
                <p class="tps-subtitle"><?php echo esc_html($page_subtitle); ?></p>
            </div>
        </div>
    </header>

    <div class="tps-shell">
        <?php
        $form_context  = 'page';
        $form_title    = $page_title;
        $form_subtitle = $page_subtitle;
        require TPS_PLUGIN_PATH . 'modules/customers/customers-form-partial.php';
        ?>
    </div>
</div>
