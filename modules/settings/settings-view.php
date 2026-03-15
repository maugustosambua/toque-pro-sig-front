<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tab             = TPS_Settings_Controller::get_current_tab();
$tabs            = TPS_Settings_Controller::get_tabs();
$active_tab      = isset( $tabs[ $tab ] ) ? $tabs[ $tab ] : $tabs['ui'];
$fields          = TPS_Settings_Controller::get_tab_fields( $tab );
$settings        = wp_parse_args( get_option( 'tps_settings', array() ), TPS_Settings_Controller::get_defaults() );
$company_name    = isset( $settings['company_name'] ) ? (string) $settings['company_name'] : '';
$company_nuit    = isset( $settings['company_nuit'] ) ? (string) $settings['company_nuit'] : '';
?>

<div class="wrap tps-settings-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div class="tps-header-content">
                <span class="tps-eyebrow">Configuracoes</span>
                <h1>Centro de Configuracoes</h1>
                <p class="tps-subtitle">Gerencie interface, identidade da empresa, impostos e numeracao sem sair do frontend.</p>
            </div>
            <div class="tps-header-highlight" aria-label="Resumo actual">
                <span class="tps-header-highlight-label">Empresa activa</span>
                <strong><?php echo esc_html( '' !== $company_name ? $company_name : get_bloginfo( 'name' ) ); ?></strong>
                <span><?php echo esc_html( '' !== $company_nuit ? 'NUIT ' . $company_nuit : 'NUIT ainda nao definido' ); ?></span>
            </div>
        </div>
    </section>

    <div class="tps-settings-layout">
        <aside class="tps-settings-sidebar">
            <nav class="tps-tabs" aria-label="Abas de configuracoes">
                <?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
                    <a href="<?php echo esc_url( tps_get_settings_tab_url( $tab_key ) ); ?>" class="tps-tab <?php echo $tab === $tab_key ? 'tps-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $tab_data['icon'] ); ?> tps-icon" aria-hidden="true"></span>
                        <span class="tps-tab-copy">
                            <strong><?php echo esc_html( $tab_data['label'] ); ?></strong>
                            <small><?php echo esc_html( $tab_data['eyebrow'] ); ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <section class="tps-settings-main">
            <div class="tps-panel tps-settings-panel">
                <div class="tps-panel-head tps-settings-panel-head">
                    <div>
                        <span class="tps-eyebrow"><?php echo esc_html( $active_tab['eyebrow'] ); ?></span>
                        <h2><?php echo esc_html( $active_tab['title'] ); ?></h2>
                        <p class="tps-subtitle"><?php echo esc_html( $active_tab['description'] ); ?></p>
                    </div>
                    <div class="tps-settings-status">
                        <span class="dashicons <?php echo esc_attr( $active_tab['icon'] ); ?>" aria-hidden="true"></span>
                        <span>Aplicado em todo o sistema</span>
                    </div>
                </div>

                <form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="tps-settings-form">
                    <?php settings_fields( 'tps_settings' ); ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( TPS_Settings_Controller::get_tab_form_url( $tab ) ); ?>">

                    <div class="tps-settings-fields">
                        <?php foreach ( $fields as $field ) : ?>
                            <?php
                            $field_key   = isset( $field['key'] ) ? (string) $field['key'] : '';
                            $field_value = TPS_Settings_Controller::get_setting_value( $field_key );
                            $field_type  = isset( $field['type'] ) ? (string) $field['type'] : 'text';
                            ?>
                            <div class="tps-settings-field">
                                <label for="tps-field-<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
                                <input
                                    id="tps-field-<?php echo esc_attr( $field_key ); ?>"
                                    type="<?php echo esc_attr( $field_type ); ?>"
                                    name="tps_settings[<?php echo esc_attr( $field_key ); ?>]"
                                    value="<?php echo esc_attr( $field_value ); ?>"
                                    placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"
                                    <?php echo isset( $field['min'] ) ? 'min="' . esc_attr( (string) $field['min'] ) . '"' : ''; ?>
                                    <?php echo isset( $field['max'] ) ? ' max="' . esc_attr( (string) $field['max'] ) . '"' : ''; ?>
                                    <?php echo isset( $field['step'] ) ? ' step="' . esc_attr( (string) $field['step'] ) . '"' : ''; ?>
                                >
                                <?php if ( ! empty( $field['description'] ) ) : ?>
                                    <p class="tps-field-description"><?php echo esc_html( $field['description'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="tps-settings-footer">
                        <div class="tps-settings-footer-note">
                            <span class="dashicons dashicons-saved" aria-hidden="true"></span>
                            <span>As alteracoes ficam disponiveis imediatamente nas listagens, documentos e cabecalhos.</span>
                        </div>
                        <?php submit_button( 'Guardar configuracoes', 'primary', 'submit', false ); ?>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
