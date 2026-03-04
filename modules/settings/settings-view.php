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

<style>
    .tps-settings-modern {
        --tps-panel: #ffffff;
        --tps-border: #dde5ef;
        --tps-text: #1a2433;
        --tps-text-muted: #5f6b7a;
        --tps-accent: #0f5ea8;
        margin-top: 14px;
    }
    .tps-settings-modern .tps-header {
        background: linear-gradient(115deg, #f8fbff 0%, #edf4fc 55%, #e4edf8 100%);
        border: 1px solid var(--tps-border);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }
    .tps-settings-modern h1 {
        margin: 0;
        color: var(--tps-text);
    }
    .tps-settings-modern .tps-subtitle {
        margin: 6px 0 0;
        color: var(--tps-text-muted);
    }
    .tps-settings-modern .tps-icon {
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        margin-right: 4px;
    }
    .tps-settings-modern .tps-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .tps-settings-modern .tps-tab {
        text-decoration: none;
        color: var(--tps-text-muted);
        background: #fff;
        border: 1px solid var(--tps-border);
        border-radius: 999px;
        padding: 8px 12px;
        font-weight: 600;
        transition: all .16s ease;
    }
    .tps-settings-modern .tps-tab-active {
        color: #fff;
        background: var(--tps-accent);
        border-color: var(--tps-accent);
    }
    .tps-settings-modern .tps-tab:hover { border-color: #b8cbe3; background: #f6faff; color: #123f67; transform: translateY(-1px); }
    .tps-settings-modern .tps-tab-active:hover { color: #fff; background: #0d528f; border-color: #0d528f; }
    .tps-settings-modern .tps-tab:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-settings-modern .tps-panel {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 12px;
        padding: 14px 16px;
        transition: all .16s ease;
    }
    .tps-settings-modern .tps-panel:hover { border-color: #c7d5e7; box-shadow: 0 8px 16px rgba(26, 36, 51, .06); }
    .tps-settings-modern .form-table th {
        color: var(--tps-text);
    }
    .tps-settings-modern .form-table td p.description {
        color: var(--tps-text-muted);
    }
    .tps-settings-modern .form-table tr { transition: all .16s ease; }
    .tps-settings-modern .form-table tr:hover th,
    .tps-settings-modern .form-table tr:hover td { background: #fbfdff; }
    .tps-settings-modern .button-primary {
        border-radius: 8px;
        transition: all .16s ease;
    }
    .tps-settings-modern .button-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(26, 36, 51, .10); }
    .tps-settings-modern .button-primary:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
</style>

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
