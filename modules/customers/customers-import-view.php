<?php // modules/customers/customers-import-view.php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$back_url = admin_url('admin.php?page=tps-customers');
?>

<div class="wrap tps-import-modern">
    <section class="tps-header">
        <div class="tps-header-row">
            <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1>Importar Clientes</h1>
                <p class="tps-subtitle">Importe em massa a sua base de clientes usando um ficheiro CSV.</p>
            </div>
        </div>
    </section>

    <section class="tps-grid">
        <article class="tps-card">
            <h2 class="tps-title-sm">Carregar Ficheiro</h2>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tps_import_customers">
                <?php wp_nonce_field('tps_import_customers'); ?>

                <div id="tps-drop-zone">
                    <strong>Clique para selecionar um ficheiro CSV</strong><br>
                    <span class="description">ou arraste e largue aqui</span>
                    <input type="file" id="tps_csv_file" name="file" accept=".csv" required class="hidden">
                </div>

                <p id="tps-file-name" class="description"></p>
                <p id="tps-file-error" class="description" hidden></p>

                <p class="tps-mt-10">
                    <?php submit_button('Importar Clientes', 'primary', 'submit', false, array( 'id' => 'tps-import-submit', 'disabled' => 'disabled' )); ?>
                </p>
            </form>
        </article>

        <article class="tps-card">
            <h2 class="tps-title-sm">Requisitos do CSV</h2>
            <ul class="tps-list">
                <li>A extensão do ficheiro deve ser <code>.csv</code>.</li>
                <li>A primeira linha deve conter as colunas de cabeçalho.</li>
                <li>Colunas obrigatórias: <code>type, name, nuit, email, phone, address, city</code>.</li>
                <li>Valores permitidos para <code>type</code>: <strong>individual</strong> ou <strong>company</strong>.</li>
            </ul>
        </article>
    </section>
</div>

