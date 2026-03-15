<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Mapa de capabilities do plugin por acao.
function tps_get_capability_map() {
    return array(
        'emitir'      => 'tps_sig_emitir_documentos',
        'cancelar'    => 'tps_sig_cancelar_documentos',
        'receber'     => 'tps_sig_registar_recebimentos',
        'exportar'    => 'tps_sig_exportar_dados',
        'fiscal'      => 'tps_sig_gerir_regras_fiscais',
        'admin'       => 'tps_sig_admin_operacional',
        'super_admin' => 'tps_sig_super_admin_programador',
    );
}

// Mapa legado para retrocompatibilidade de permissões já atribuídas.
function tps_get_legacy_capability_map() {
    return array(
        'emitir'      => array( 'tps_emit_documents' ),
        'cancelar'    => array( 'tps_cancel_documents' ),
        'receber'     => array( 'tps_receive_payments' ),
        'exportar'    => array( 'tps_export_data' ),
        'fiscal'      => array( 'tps_manage_fiscal_rules' ),
        'admin'       => array( 'tps_admin' ),
        'super_admin' => array(),
    );
}

// Resolve o nome da capability a partir da chave logica.
function tps_get_capability( $key ) {
    $map = tps_get_capability_map();

    return isset( $map[ $key ] ) ? (string) $map[ $key ] : '';
}

// Verifica permissao por chave logica (com fallback para administradores).
function tps_current_user_can( $key, $user = null ) {
    $capability = tps_get_capability( $key );
    if ( '' === $capability ) {
        return false;
    }

    $legacy_map  = tps_get_legacy_capability_map();
    $legacy_caps = isset( $legacy_map[ $key ] ) && is_array( $legacy_map[ $key ] ) ? $legacy_map[ $key ] : array();

    if ( null === $user ) {
        $can = current_user_can( $capability );

        if ( ! $can ) {
            foreach ( $legacy_caps as $legacy_cap ) {
                if ( current_user_can( $legacy_cap ) ) {
                    $can = true;
                    break;
                }
            }
        }
    } else {
        $can = user_can( $user, $capability );

        if ( ! $can ) {
            foreach ( $legacy_caps as $legacy_cap ) {
                if ( user_can( $user, $legacy_cap ) ) {
                    $can = true;
                    break;
                }
            }
        }
    }

    return (bool) apply_filters( 'tps_current_user_can', $can, $key, $capability, $user );
}

// Verifica se utilizador tem pelo menos uma das permissoes informadas.
function tps_current_user_can_any( $keys, $user = null ) {
    if ( ! is_array( $keys ) || empty( $keys ) ) {
        return false;
    }

    foreach ( $keys as $key ) {
        if ( tps_current_user_can( $key, $user ) ) {
            return true;
        }
    }

    return false;
}

// Permissao geral para entrar no frontend do plugin.
function tps_current_user_can_access_plugin( $user = null ) {
    return tps_current_user_can_any( array( 'admin', 'emitir', 'cancelar', 'receber', 'exportar', 'fiscal' ), $user );
}

// Verifica se utilizador é o super admin técnico (programador).
function tps_current_user_is_programmer_super_admin( $user = null ) {
    return tps_current_user_can( 'super_admin', $user );
}

// Lista de capacidades nativas de gestão de plugins/temas/core que ficam exclusivas do programador.
function tps_get_restricted_wp_admin_capabilities() {
    return array(
        'install_plugins',
        'update_plugins',
        'delete_plugins',
        'activate_plugins',
        'deactivate_plugins',
        'edit_plugins',
        'upload_plugins',
        'install_themes',
        'update_themes',
        'delete_themes',
        'update_core',
    );
}

// Bloqueia capacidades de gestão de plugins/temas/core para não-programadores.
function tps_guard_plugin_management_map_meta_cap( $required_caps, $cap, $user_id, $args ) {
    $restricted_caps = tps_get_restricted_wp_admin_capabilities();
    if ( ! in_array( $cap, $restricted_caps, true ) ) {
        return $required_caps;
    }

    if ( tps_current_user_is_programmer_super_admin( (int) $user_id ) ) {
        return $required_caps;
    }

    return array( 'do_not_allow' );
}

// Inicializa guardas de segurança de capabilities globais.
function tps_init_capability_guards() {
    add_filter( 'map_meta_cap', 'tps_guard_plugin_management_map_meta_cap', 10, 4 );
}

// Define grants padrao por perfil (role) do WordPress.
function tps_get_role_capability_grants() {
    return array(
        'tps_programador' => array( 'admin', 'emitir', 'cancelar', 'receber', 'exportar', 'fiscal', 'super_admin' ),
        'administrator'   => array( 'admin', 'emitir', 'cancelar', 'receber', 'exportar', 'fiscal' ),
        'editor'          => array( 'emitir', 'cancelar', 'receber', 'exportar', 'fiscal' ),
        'author'          => array( 'emitir', 'receber' ),
    );
}

// Sincroniza capabilities granulares nos perfis padrao suportados.
function tps_sync_role_capabilities() {
    if ( ! get_role( 'tps_programador' ) ) {
        add_role(
            'tps_programador',
            'Programador',
            array(
                'read' => true,
            )
        );
    }

    $all_keys = array_keys( tps_get_capability_map() );
    $grants   = tps_get_role_capability_grants();

    foreach ( $grants as $role_name => $allowed_keys ) {
        $role = get_role( $role_name );
        if ( ! $role ) {
            continue;
        }

        $allowed_keys = is_array( $allowed_keys ) ? $allowed_keys : array();

        foreach ( $all_keys as $key ) {
            $capability = tps_get_capability( $key );
            if ( '' === $capability ) {
                continue;
            }

            if ( in_array( $key, $allowed_keys, true ) ) {
                $role->add_cap( $capability );
            } else {
                $role->remove_cap( $capability );
            }
        }
    }

    // Remove capacidades técnicas nativas de plugins/temas/core de perfis não programadores.
    $restricted_wp_caps = tps_get_restricted_wp_admin_capabilities();
    foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager' ) as $wp_role_name ) {
        $wp_role = get_role( $wp_role_name );
        if ( ! $wp_role ) {
            continue;
        }

        foreach ( $restricted_wp_caps as $restricted_wp_cap ) {
            $wp_role->remove_cap( $restricted_wp_cap );
        }
    }

    // Garante acesso técnico completo apenas para o perfil Programador.
    $programador_role = get_role( 'tps_programador' );
    if ( $programador_role ) {
        foreach ( $restricted_wp_caps as $restricted_wp_cap ) {
            $programador_role->add_cap( $restricted_wp_cap );
        }
        $programador_role->add_cap( 'manage_options' );
    }
}

// Garante sincronizacao quando houver mudancas de versao da matriz de perfis.
function tps_maybe_sync_role_capabilities() {
    $version_key = 'tps_capabilities_matrix_version';
    $target      = '2026-03-15-2';
    $current     = (string) get_option( $version_key, '' );

    if ( $current === $target ) {
        return;
    }

    tps_sync_role_capabilities();
    update_option( $version_key, $target );
}
