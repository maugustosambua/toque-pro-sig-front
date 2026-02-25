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

<div class="wrap">
    <h1 class="wp-heading-inline">Toque Pro SiG â€“ Settings</h1>
    <hr class="wp-header-end">

    <!-- Tabs nativas -->
    <h2 class="nav-tab-wrapper">
        <a href="?page=tps-settings&tab=ui"
           class="nav-tab <?php echo $tab === 'ui' ? 'nav-tab-active' : ''; ?>">
            UI
        </a>
        <a href="?page=tps-settings&tab=company"
           class="nav-tab <?php echo $tab === 'company' ? 'nav-tab-active' : ''; ?>">
            Company
        </a>
        <a href="?page=tps-settings&tab=tax"
           class="nav-tab <?php echo $tab === 'tax' ? 'nav-tab-active' : ''; ?>">
            Tax
        </a>
        <a href="?page=tps-settings&tab=numbering"
           class="nav-tab <?php echo $tab === 'numbering' ? 'nav-tab-active' : ''; ?>">
            Numbering
        </a>
    </h2>

    <form method="post" action="options.php">

        <?php
        // Campos de segurança da API de configurações
        settings_fields('tps_settings');

        // Secções conforme tab activa
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

        // Botão guardar
        submit_button();
        ?>
    </form>
</div>

