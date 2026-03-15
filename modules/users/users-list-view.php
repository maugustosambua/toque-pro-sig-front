<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = TPS_Users_Controller::get_list_data();
?>
<div class="wrap tps-users-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div>
                <h1>Utilizadores</h1>
                <p class="tps-subtitle">Gestao completa de contas WordPress com criacao, edicao e remocao no frontend.</p>
            </div>
            <div class="tps-actions">
                <a href="<?php echo esc_url( tps_get_page_url( 'tps-users-add' ) ); ?>" class="tps-btn tps-btn-primary"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Novo utilizador</a>
                <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-external tps-icon" aria-hidden="true"></span>Abrir no admin</a>
            </div>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-groups tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Total</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format_i18n( $data['total_users'] ) ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-shield tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Administradores</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format_i18n( $data['admins_count'] ) ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-visibility tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Na vista</span></p>
            <p id="tps-users-current-count" class="tps-stat-value"><?php echo esc_html( number_format_i18n( $data['current_count'] ) ); ?></p>
        </article>
    </section>

    <form id="tps-users-toolbar" class="tps-toolbar" action="">
        <input type="hidden" name="tps_view" value="tps-users">
        <div>
        <input id="tps-users-search" class="tps-search tps-input" type="search" name="search" value="<?php echo esc_attr( $data['search'] ); ?>" placeholder="Pesquisar por nome, login ou email">
        </div>
        <div>
        <select id="tps-users-role" class="tps-select" name="role">
            <option value="">Todos os perfis</option>
            <?php foreach ( $data['role_options'] as $role_key => $role_data ) : ?>
                <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $data['role'], $role_key ); ?>><?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?></option>
            <?php endforeach; ?>
        </select>
        </div>
        <div>
        <div class="tps-actions">
            <button id="tps-users-reset" type="button" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-update tps-icon" aria-hidden="true"></span>Limpar</button>
        </div>
        </div>
    </form>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Utilizador</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Registado</th>
                    <th>Estado</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody id="tps-users-tbody"></tbody>
        </table>
        <div id="tps-users-empty" class="tps-empty" hidden>Nenhum utilizador encontrado com os filtros actuais.</div>
        <div class="tps-pagination">
            <button id="tps-users-prev" type="button" class="tps-page-btn"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>Anterior</button>
            <span id="tps-users-page">Pagina 1</span>
            <button id="tps-users-next" type="button" class="tps-page-btn">Seguinte<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
        </div>
    </section>
</div>
