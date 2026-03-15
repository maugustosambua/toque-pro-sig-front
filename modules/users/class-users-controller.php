<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Users_Controller {
    public static function init() {
        add_action( 'admin_post_tps_save_user', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_tps_delete_user', array( __CLASS__, 'delete' ) );
        add_action( 'wp_ajax_tps_ajax_users_list', array( __CLASS__, 'list_ajax' ) );
    }

    public static function get_list_data() {
        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $paged  = isset( $_GET['users_page'] ) ? max( 1, (int) $_GET['users_page'] ) : 1;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $role   = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';

        $editable_roles = get_editable_roles();
        if ( '' !== $role && ! isset( $editable_roles[ $role ] ) ) {
            $role = '';
        }

        $query_args = array(
            'number'         => $per_page,
            'offset'         => ( $paged - 1 ) * $per_page,
            'orderby'        => 'registered',
            'order'          => 'DESC',
            'count_total'    => true,
            'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
        );

        if ( '' !== $search ) {
            $query_args['search'] = '*' . $search . '*';
        }

        if ( '' !== $role ) {
            $query_args['role'] = $role;
        }

        $query       = new WP_User_Query( $query_args );
        $users       = $query->get_results();
        $total_items = (int) $query->get_total();
        $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
        $counts      = count_users();
        $role_counts = isset( $counts['avail_roles'] ) && is_array( $counts['avail_roles'] ) ? $counts['avail_roles'] : array();

        return array(
            'items'        => is_array( $users ) ? $users : array(),
            'search'       => $search,
            'role'         => $role,
            'paged'        => min( $paged, $total_pages ),
            'total_pages'  => $total_pages,
            'role_options' => $editable_roles,
            'total_users'  => isset( $counts['total_users'] ) ? (int) $counts['total_users'] : $total_items,
            'admins_count' => isset( $role_counts['administrator'] ) ? (int) $role_counts['administrator'] : 0,
            'current_count' => count( is_array( $users ) ? $users : array() ),
        );
    }

    public static function get_role_label( $role ) {
        $editable_roles = get_editable_roles();

        if ( isset( $editable_roles[ $role ]['name'] ) ) {
            return translate_user_role( $editable_roles[ $role ]['name'] );
        }

        return ucfirst( str_replace( '_', ' ', (string) $role ) );
    }

    // Lista AJAX paginada de utilizadores.
    public static function list_ajax() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_send_json_error( array( 'message' => 'Permissao negada.' ), 403 );
        }

        check_ajax_referer( 'tps_ajax_users_list', 'nonce' );

        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $role   = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';

        $editable_roles = get_editable_roles();
        if ( '' !== $role && ! isset( $editable_roles[ $role ] ) ) {
            $role = '';
        }

        $query_args = array(
            'number'         => $per_page,
            'offset'         => ( $paged - 1 ) * $per_page,
            'orderby'        => 'registered',
            'order'          => 'DESC',
            'count_total'    => true,
            'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
        );

        if ( '' !== $search ) {
            $query_args['search'] = '*' . $search . '*';
        }

        if ( '' !== $role ) {
            $query_args['role'] = $role;
        }

        $query       = new WP_User_Query( $query_args );
        $users       = $query->get_results();
        $total_items = (int) $query->get_total();
        $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
        $current_id  = get_current_user_id();

        $rows = array();
        foreach ( (array) $users as $user ) {
            if ( ! $user instanceof WP_User ) {
                continue;
            }

            $user_id      = (int) $user->ID;
            $roles        = is_array( $user->roles ) ? $user->roles : array();
            $primary_role = isset( $roles[0] ) ? (string) $roles[0] : '';
            $is_current   = $current_id === $user_id;

            $rows[] = array(
                'id'            => $user_id,
                'display_name'  => (string) $user->display_name,
                'user_login'    => (string) $user->user_login,
                'user_email'    => (string) $user->user_email,
                'role'          => self::get_role_label( $primary_role ),
                'registered_at' => wp_date( 'd/m/Y', strtotime( (string) $user->user_registered ) ),
                'status_label'  => $is_current ? 'Sessao actual' : 'Activo',
                'status_class'  => $is_current ? 'tps-badge-draft' : 'tps-badge-neutral',
                'role_class'    => 'tps-badge-issued',
                'is_current'    => $is_current,
                'edit_url'      => tps_get_page_url( 'tps-users-add', array( 'user_id' => $user_id ) ),
                'delete_url'    => $is_current ? '' : wp_nonce_url( add_query_arg( array( 'action' => 'tps_delete_user', 'user_id' => $user_id ), tps_get_action_url() ), 'tps_delete_user' ),
            );
        }

        wp_send_json_success(
            array(
                'rows'         => $rows,
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
                'current_page' => min( $paged, $total_pages ),
                'current_count'=> count( $rows ),
            )
        );
    }

    public static function get_user( $user_id ) {
        $user_id = (int) $user_id;
        if ( $user_id <= 0 ) {
            return null;
        }

        $user = get_userdata( $user_id );

        return $user instanceof WP_User ? $user : null;
    }

    public static function save() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_save_user' );

        $user_id      = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
        $user_login   = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
        $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
        $user_email   = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
        $role         = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
        $first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $user_pass    = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : '';

        $editable_roles = get_editable_roles();
        if ( ! isset( $editable_roles[ $role ] ) ) {
            $role = '';
        }

        $redirect = tps_get_page_url( 'tps-users-add' );
        if ( $user_id > 0 ) {
            $redirect = add_query_arg( 'user_id', $user_id, $redirect );
        }

        if ( '' === $display_name || '' === $user_email || '' === $role || ( 0 === $user_id && '' === $user_login ) ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'user_invalid_data', 'error' ) ) );
            exit;
        }

        if ( ! is_email( $user_email ) ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'user_invalid_data', 'error' ) ) );
            exit;
        }

        if ( 0 === $user_id && username_exists( $user_login ) ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'user_duplicate_login', 'error' ) ) );
            exit;
        }

        $email_owner = email_exists( $user_email );
        if ( $email_owner && (int) $email_owner !== $user_id ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'user_duplicate_email', 'error' ) ) );
            exit;
        }

        $payload = array(
            'display_name' => $display_name,
            'user_email'   => $user_email,
            'role'         => $role,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
        );

        if ( '' !== $user_pass ) {
            $payload['user_pass'] = $user_pass;
        }

        if ( $user_id > 0 ) {
            $before_user = self::get_user( $user_id );

            if ( ! $before_user ) {
                wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), 'user_not_found', 'error' ) ) );
                exit;
            }

            $payload['ID'] = $user_id;
            $result        = wp_update_user( $payload );
            $notice        = 'user_updated';
        } else {
            $payload['user_login'] = $user_login;

            if ( '' === $user_pass ) {
                $payload['user_pass'] = wp_generate_password( 16, true, true );
            }

            $result = wp_insert_user( $payload );
            $notice = 'user_created';
        }

        if ( is_wp_error( $result ) ) {
            $error_code = $result->get_error_code();
            $notice_code = 'user_invalid_data';

            if ( 'existing_user_login' === $error_code ) {
                $notice_code = 'user_duplicate_login';
            } elseif ( 'existing_user_email' === $error_code ) {
                $notice_code = 'user_duplicate_email';
            }

            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, $notice_code, 'error' ) ) );
            exit;
        }

        if ( 'user_updated' === $notice && isset( $before_user ) ) {
            $after_user = self::get_user( $user_id );
            tps_audit_log( 'user_updated', 'user', $user_id, $before_user, $after_user );
        }

        if ( 'user_created' === $notice ) {
            $created_user_id = (int) $result;
            $after_user      = self::get_user( $created_user_id );
            tps_audit_log( 'user_created', 'user', $created_user_id, null, $after_user );
        }

        wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), $notice, 'success' ) ) );
        exit;
    }

    public static function delete() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_delete_user' );

        $user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
        if ( $user_id <= 0 ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), 'user_delete_invalid', 'error' ) ) );
            exit;
        }

        $current_user_id = get_current_user_id();
        if ( $user_id === $current_user_id ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), 'user_delete_current', 'error' ) ) );
            exit;
        }

        $user = self::get_user( $user_id );
        if ( ! $user ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), 'user_not_found', 'error' ) ) );
            exit;
        }

        $before_user = $user;

        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            $counts       = count_users();
            $admin_count  = isset( $counts['avail_roles']['administrator'] ) ? (int) $counts['avail_roles']['administrator'] : 0;

            if ( $admin_count <= 1 ) {
                wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), 'user_delete_last_admin', 'error' ) ) );
                exit;
            }
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $user_id, $current_user_id );

        tps_audit_log( 'user_deleted', 'user', $user_id, $before_user, null );

        wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-users' ), 'user_deleted', 'success' ) ) );
        exit;
    }
}
