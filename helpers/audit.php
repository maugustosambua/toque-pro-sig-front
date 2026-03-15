<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Nome da tabela de trilha de auditoria.
function tps_audit_table() {
    global $wpdb;

    return $wpdb->prefix . 'tps_audit_trail';
}

// Regista um evento de auditoria.
function tps_audit_log( $event_type, $entity_type, $entity_id = 0, $before = null, $after = null, $meta = array() ) {
    global $wpdb;

    $event_type  = sanitize_key( (string) $event_type );
    $entity_type = sanitize_key( (string) $entity_type );

    if ( '' === $event_type || '' === $entity_type ) {
        return false;
    }

    $before_state = tps_audit_normalize_state( $before );
    $after_state  = tps_audit_normalize_state( $after );
    $meta_state   = tps_audit_normalize_state( $meta );

    $before_json = null;
    $after_json  = null;
    $meta_json   = null;

    if ( null !== $before_state ) {
        $before_json = wp_json_encode( $before_state );
    }

    if ( null !== $after_state ) {
        $after_json = wp_json_encode( $after_state );
    }

    if ( null !== $meta_state ) {
        $meta_json = wp_json_encode( $meta_state );
    }

    $ip_address = tps_audit_request_ip();
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

    return false !== $wpdb->insert(
        tps_audit_table(),
        array(
            'event_type'   => $event_type,
            'entity_type'  => $entity_type,
            'entity_id'    => (int) $entity_id > 0 ? (int) $entity_id : null,
            'user_id'      => (int) get_current_user_id() > 0 ? (int) get_current_user_id() : null,
            'before_state' => $before_json,
            'after_state'  => $after_json,
            'meta'         => $meta_json,
            'ip_address'   => '' !== $ip_address ? $ip_address : null,
            'user_agent'   => '' !== $user_agent ? substr( $user_agent, 0, 255 ) : null,
            'created_at'   => current_time( 'mysql' ),
        ),
        array(
            '%s',
            '%s',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        )
    );
}

// Normaliza estrutura before/after/meta para auditoria.
function tps_audit_normalize_state( $value ) {
    if ( null === $value ) {
        return null;
    }

    if ( is_object( $value ) ) {
        if ( $value instanceof WP_User ) {
            $value = array(
                'id'           => (int) $value->ID,
                'user_login'   => (string) $value->user_login,
                'user_email'   => (string) $value->user_email,
                'display_name' => (string) $value->display_name,
                'first_name'   => (string) $value->first_name,
                'last_name'    => (string) $value->last_name,
                'roles'        => array_values( array_map( 'strval', (array) $value->roles ) ),
            );
        } else {
            $value = (array) $value;
        }
    }

    if ( is_array( $value ) ) {
        $normalized = array();

        foreach ( $value as $key => $item ) {
            $safe_key = is_string( $key ) ? sanitize_key( $key ) : $key;

            if ( is_string( $safe_key ) && tps_audit_is_sensitive_key( $safe_key ) ) {
                $normalized[ $safe_key ] = '***';
                continue;
            }

            $normalized[ $safe_key ] = tps_audit_normalize_state( $item );
        }

        return $normalized;
    }

    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_int( $value ) || is_float( $value ) ) {
        return $value;
    }

    if ( is_string( $value ) ) {
        return sanitize_textarea_field( $value );
    }

    return (string) $value;
}

// Identifica chaves sensiveis para mascarar em auditoria.
function tps_audit_is_sensitive_key( $key ) {
    $sensitive_tokens = array( 'pass', 'password', 'token', 'secret', 'nonce' );

    foreach ( $sensitive_tokens as $token ) {
        if ( false !== strpos( (string) $key, $token ) ) {
            return true;
        }
    }

    return false;
}

// Resolve IP da requisicao para contexto de auditoria.
function tps_audit_request_ip() {
    $candidates = array();

    if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $forwarded = (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $parts     = array_map( 'trim', explode( ',', $forwarded ) );
        $candidates = array_merge( $candidates, $parts );
    }

    if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
        $candidates[] = (string) wp_unslash( $_SERVER['REMOTE_ADDR'] );
    }

    foreach ( $candidates as $candidate ) {
        $ip = sanitize_text_field( (string) $candidate );

        if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return $ip;
        }
    }

    return '';
}
