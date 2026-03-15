<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user_id      = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
$user         = $user_id ? TPS_Users_Controller::get_user( $user_id ) : null;
$role_options = get_editable_roles();
$page_title   = $user ? 'Editar Utilizador' : 'Adicionar Utilizador';
$page_subtitle = $user ? 'Atualize dados de acesso, perfil e password quando necessario.' : 'Crie uma nova conta para acesso ao ERP frontend.';
$back_url     = tps_get_page_url( 'tps-users' );
$user_roles   = $user instanceof WP_User ? (array) $user->roles : array();
$selected_role = isset( $user_roles[0] ) ? $user_roles[0] : 'administrator';

if ( $user_id > 0 && ! $user ) {
    wp_safe_redirect( esc_url_raw( tps_notice_url( $back_url, 'user_not_found', 'error' ) ) );
    exit;
}
?>

<div class="wrap tps-user-form">
    <header class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url( $back_url ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1><?php echo esc_html( $page_title ); ?></h1>
                <p class="tps-subtitle"><?php echo esc_html( $page_subtitle ); ?></p>
            </div>
        </div>
    </header>

    <div class="tps-shell">
        <form method="post" action="<?php echo esc_url( tps_get_action_url() ); ?>">
            <input type="hidden" name="action" value="tps_save_user">
            <?php wp_nonce_field( 'tps_save_user' ); ?>

            <?php if ( $user ) : ?>
                <input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>">
            <?php endif; ?>

            <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-user-login">Nome de utilizador</label>
                        <input id="tps-user-login" class="tps-input" type="text" name="user_login" <?php echo $user ? 'readonly' : 'required'; ?> value="<?php echo esc_attr( $user instanceof WP_User ? $user->user_login : '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-display-name">Nome de exibicao</label>
                        <input id="tps-display-name" class="tps-input" type="text" name="display_name" required value="<?php echo esc_attr( $user instanceof WP_User ? $user->display_name : '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-user-email">Email</label>
                        <input id="tps-user-email" class="tps-input" type="email" name="user_email" required value="<?php echo esc_attr( $user instanceof WP_User ? $user->user_email : '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-user-role">Perfil</label>
                        <select id="tps-user-role" class="tps-select" name="role" required>
                            <?php foreach ( $role_options as $role_key => $role_data ) : ?>
                                <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $selected_role, $role_key ); ?>><?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="tps-first-name">Primeiro nome</label>
                        <input id="tps-first-name" class="tps-input" type="text" name="first_name" value="<?php echo esc_attr( $user instanceof WP_User ? $user->first_name : '' ); ?>">
                    </div>

                    <div>
                        <label for="tps-last-name">Apelido</label>
                        <input id="tps-last-name" class="tps-input" type="text" name="last_name" value="<?php echo esc_attr( $user instanceof WP_User ? $user->last_name : '' ); ?>">
                    </div>

                    <div class="tps-field-full">
                        <label for="tps-user-pass">Password <?php echo esc_html( $user ? '(deixe vazio para manter a atual)' : '(deixe vazio para gerar automaticamente)' ); ?></label>
                        <input id="tps-user-pass" class="tps-input" type="password" name="user_pass" autocomplete="new-password" value="">
                    </div>
                </div>
            </div>

            <div class="tps-actions">
                <?php submit_button( $user ? 'Atualizar Utilizador' : 'Criar Utilizador', 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
</div>
