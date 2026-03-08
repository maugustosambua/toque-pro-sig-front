<?php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

// Tab activa (sanitize + whitelist)
$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'ui';
$allowed_tabs = array( 'ui', 'company', 'tax', 'numbering' );
if (! in_array($tab, $allowed_tabs, true)) {
    $tab = 'ui';
}

?>

<div class="wrap tps-settings-modern">
    <section class="tps-header">
        <h1><span class="dashicons dashicons-admin-generic tps-icon" aria-hidden="true"></span>Configurações do Toque Pro SiG</h1>
        <p class="tps-subtitle">Configure a interface, os dados da empresa, os impostos e a numeração.</p>
    </section>

    <nav class="tps-tabs" aria-label="Abas de configurações">
        <a href="?page=tps-settings&tab=ui" class="tps-tab <?php echo $tab === 'ui' ? 'tps-tab-active' : ''; ?>"><span class="dashicons dashicons-screenoptions tps-icon" aria-hidden="true"></span>Interface</a>
        <a href="?page=tps-settings&tab=company" class="tps-tab <?php echo $tab === 'company' ? 'tps-tab-active' : ''; ?>"><span class="dashicons dashicons-building tps-icon" aria-hidden="true"></span>Empresa</a>
        <a href="?page=tps-settings&tab=tax" class="tps-tab <?php echo $tab === 'tax' ? 'tps-tab-active' : ''; ?>"><span class="dashicons dashicons-calculator tps-icon" aria-hidden="true"></span>Impostos</a>
        <a href="?page=tps-settings&tab=numbering" class="tps-tab <?php echo $tab === 'numbering' ? 'tps-tab-active' : ''; ?>"><span class="dashicons dashicons-editor-ol tps-icon" aria-hidden="true"></span>Numeração</a>
    </nav>

    <section class="tps-panel">
        <form method="post" action="options.php">
            <?php
            settings_fields('tps_settings');

            if ($tab === 'ui') {
                do_settings_sections('tps-settings-ui');
            }

            if ($tab === 'company') {
                do_settings_sections('tps-settings-company');
            }

            if ($tab === 'tax') {
                do_settings_sections('tps-settings-tax');
            }

            if ($tab === 'numbering') {
                do_settings_sections('tps-settings-numbering');
            }

            submit_button();
            ?>
        </form>
    </section>
</div>
