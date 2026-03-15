<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! tps_current_user_can_any( array( 'admin', 'fiscal' ) ) ) {
    wp_die( 'Sem permissao para aceder a auditoria.' );
}

$data    = TPS_Audit_Controller::get_list_data();
$filters = $data['filters'];

$base_args = array(
    'tps_view'    => 'tps-audit',
    'event_type'  => $filters['event_type'],
    'entity_type' => $filters['entity_type'],
    'user_id'     => $filters['user_id'] > 0 ? $filters['user_id'] : '',
    'from_date'   => $filters['from_date'],
    'to_date'     => $filters['to_date'],
);
?>
<div class="wrap tps-audit-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div>
                <h1>Trilha de Auditoria</h1>
                <p class="tps-subtitle">Monitorize eventos críticos com estado anterior/posterior e contexto de execução.</p>
            </div>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-list-view tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Total de eventos</span></p>
            <p class="tps-stat-value"><?php echo esc_html( number_format_i18n( (int) $data['total_items'] ) ); ?></p>
        </article>
    </section>

    <form method="get" class="tps-toolbar">
        <input type="hidden" name="tps_view" value="tps-audit">

        <div>
            <label for="tps-audit-event">Evento</label>
            <select id="tps-audit-event" class="tps-select" name="event_type">
                <option value="">Todos</option>
                <?php foreach ( $data['event_options'] as $event_type ) : ?>
                    <option value="<?php echo esc_attr( $event_type ); ?>" <?php selected( $filters['event_type'], $event_type ); ?>><?php echo esc_html( $event_type ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="tps-audit-entity">Entidade</label>
            <select id="tps-audit-entity" class="tps-select" name="entity_type">
                <option value="">Todas</option>
                <?php foreach ( $data['entity_options'] as $entity_type ) : ?>
                    <option value="<?php echo esc_attr( $entity_type ); ?>" <?php selected( $filters['entity_type'], $entity_type ); ?>><?php echo esc_html( $entity_type ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="tps-audit-user">Utilizador</label>
            <select id="tps-audit-user" class="tps-select" name="user_id">
                <option value="">Todos</option>
                <?php foreach ( $data['user_options'] as $user_option ) : ?>
                    <option value="<?php echo esc_attr( (string) (int) $user_option->user_id ); ?>" <?php selected( (int) $filters['user_id'], (int) $user_option->user_id ); ?>><?php echo esc_html( $user_option->display_name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="tps-audit-from">De</label>
            <input id="tps-audit-from" class="tps-input" type="date" name="from_date" value="<?php echo esc_attr( $filters['from_date'] ); ?>">
        </div>

        <div>
            <label for="tps-audit-to">Até</label>
            <input id="tps-audit-to" class="tps-input" type="date" name="to_date" value="<?php echo esc_attr( $filters['to_date'] ); ?>">
        </div>

        <div>
            <label>&nbsp;</label>
            <div class="tps-actions">
                <button type="submit" class="tps-btn tps-btn-primary"><span class="dashicons dashicons-search" aria-hidden="true"></span>Filtrar</button>
                <a class="tps-btn tps-btn-secondary" href="<?php echo esc_url( tps_get_page_url( 'tps-audit' ) ); ?>"><span class="dashicons dashicons-update" aria-hidden="true"></span>Limpar</a>
            </div>
        </div>
    </form>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Evento</th>
                    <th>Entidade</th>
                    <th>Utilizador</th>
                    <th>Before</th>
                    <th>After</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $data['items'] ) ) : ?>
                    <tr>
                        <td colspan="7">
                            <div class="tps-empty">Nenhum evento encontrado com os filtros informados.</div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $data['items'] as $item ) : ?>
                        <?php
                        $has_before = ! empty( $item->before_state );
                        $has_after  = ! empty( $item->after_state );

                        $before_data = json_decode( (string) $item->before_state, true );
                        $after_data  = json_decode( (string) $item->after_state, true );
                        $meta_data   = json_decode( (string) $item->meta, true );

                        $detail_payload = wp_json_encode(
                            array(
                                'id'         => (int) $item->id,
                                'created_at' => (string) $item->created_at,
                                'event_type' => (string) $item->event_type,
                                'entity'     => array(
                                    'type' => (string) $item->entity_type,
                                    'id'   => ! empty( $item->entity_id ) ? (int) $item->entity_id : null,
                                ),
                                'user'       => array(
                                    'id'   => ! empty( $item->user_id ) ? (int) $item->user_id : null,
                                    'name' => ! empty( $item->user_display_name ) ? (string) $item->user_display_name : 'Sistema',
                                ),
                                'context'    => array(
                                    'ip_address' => ! empty( $item->ip_address ) ? (string) $item->ip_address : '',
                                    'user_agent' => ! empty( $item->user_agent ) ? (string) $item->user_agent : '',
                                ),
                                'before'     => is_array( $before_data ) ? $before_data : null,
                                'after'      => is_array( $after_data ) ? $after_data : null,
                                'meta'       => is_array( $meta_data ) ? $meta_data : null,
                            ),
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                        ?>
                        <tr>
                            <td><?php echo esc_html( wp_date( 'd/m/Y H:i:s', strtotime( (string) $item->created_at ) ) ); ?></td>
                            <td><span class="tps-badge tps-badge-neutral"><?php echo esc_html( (string) $item->event_type ); ?></span></td>
                            <td><?php echo esc_html( (string) $item->entity_type . ( ! empty( $item->entity_id ) ? ' #' . (int) $item->entity_id : '' ) ); ?></td>
                            <td><?php echo esc_html( ! empty( $item->user_display_name ) ? (string) $item->user_display_name : 'Sistema' ); ?></td>
                            <td><?php echo $has_before ? '<span class="tps-badge tps-badge-issued">Sim</span>' : '<span class="tps-badge tps-badge-neutral">Não</span>'; ?></td>
                            <td><?php echo $has_after ? '<span class="tps-badge tps-badge-issued">Sim</span>' : '<span class="tps-badge tps-badge-neutral">Não</span>'; ?></td>
                            <td>
                                <button type="button" class="tps-row-btn" data-tps-audit-open="1" data-tps-audit-payload="tps-audit-json-<?php echo esc_attr( (string) (int) $item->id ); ?>">
                                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                    Ver
                                </button>
                                <script type="application/json" id="tps-audit-json-<?php echo esc_attr( (string) (int) $item->id ); ?>"><?php echo esc_html( (string) $detail_payload ); ?></script>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ( $data['total_pages'] > 1 ) : ?>
            <div class="tps-pagination">
                <?php if ( $data['paged'] > 1 ) : ?>
                    <a class="tps-page-btn" href="<?php echo esc_url( add_query_arg( array_merge( $base_args, array( 'audit_page' => $data['paged'] - 1 ) ), tps_get_page_url( 'tps-audit' ) ) ); ?>"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>Anterior</a>
                <?php else : ?>
                    <span class="tps-page-btn" aria-disabled="true">Anterior</span>
                <?php endif; ?>

                <span>Página <?php echo esc_html( (string) $data['paged'] ); ?> de <?php echo esc_html( (string) $data['total_pages'] ); ?></span>

                <?php if ( $data['paged'] < $data['total_pages'] ) : ?>
                    <a class="tps-page-btn" href="<?php echo esc_url( add_query_arg( array_merge( $base_args, array( 'audit_page' => $data['paged'] + 1 ) ), tps_get_page_url( 'tps-audit' ) ) ); ?>">Seguinte<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>
                <?php else : ?>
                    <span class="tps-page-btn" aria-disabled="true">Seguinte</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <div id="tps-audit-modal" class="tps-modal" hidden aria-hidden="true">
        <div class="tps-modal__dialog tps-audit-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="tps-audit-modal-title">
            <div class="tps-customer-modal-head">
                <h2 id="tps-audit-modal-title">Detalhes do Evento</h2>
                <button type="button" class="tps-btn tps-btn-secondary" data-tps-audit-close="1">
                    <span class="dashicons dashicons-no" aria-hidden="true"></span>
                    Fechar
                </button>
            </div>
            <div class="tps-modal__body">
                <pre id="tps-audit-modal-json" class="tps-audit-json">{}</pre>
            </div>
        </div>
    </div>
</div>
