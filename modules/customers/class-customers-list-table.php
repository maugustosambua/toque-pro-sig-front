<?php

// modules/customers/class-customers-list-table.php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class TPS_Customers_List_Table extends WP_List_Table
{
    // Define colunas
    public function get_columns()
    {
        return array(
            'name'    => 'Name',
            'type'    => 'Type',
            'nuit'    => 'NUIT',
            'phone'   => 'Phone',
            'city'    => 'City',
            'actions' => 'Actions',
        );
    }

    // Define colunas ordenáveis
    protected function get_sortable_columns()
    {
        return array(
            'name' => array( 'name', true ),
            'type' => array( 'type', false ),
        );
    }

    // Coluna Nome (sem ações de hover)
    protected function column_name($item)
    {
        return '<strong>' . esc_html($item->name) . '</strong>';
    }

    // Renderiza colunas padrão
    protected function column_default($item, $column_name)
    {
        return esc_html($item->$column_name ?? '');
    }

    // Coluna Ações fixa
    protected function column_actions($item)
    {

        $edit_url = admin_url('admin.php?page=tps-customers-add&customer_id=' . (int) $item->id);
        $delete_url = wp_nonce_url(admin_url('admin-post.php?action=tps_delete_customer&customer_id=' . (int) $item->id), 'tps_delete_customer');
        $export_url = wp_nonce_url(admin_url('admin-post.php?action=tps_export_customer&customer_id=' . (int) $item->id), 'tps_export_customer');
        $edit = '<a class="button button-small" href="' . esc_url($edit_url) . '">Edit</a>';
        $export = '<a class="button button-small" href="' . esc_url($export_url) . '">Export</a>';
        $delete = '<a class="button button-small" href="' . esc_url($delete_url) . '" onclick="return confirm(\'Delete this customer?\');">Delete</a>';
        return $edit . ' ' . $export . ' ' . $delete;
    }

    // Links rápidos por tipo (preserva pesquisa)
    public function get_views()
    {

        $current = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $search  = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $base = admin_url('admin.php?page=tps-customers');
// Preserva pesquisa ao trocar o tipo
        if ($search !== '') {
            $base = add_query_arg('s', $search, $base);
        }

        $all = TPS_Customers_Model::count_customers(array(
            'search' => $search,
        ));
        $individual = TPS_Customers_Model::count_customers(array(
            'type'   => 'individual',
            'search' => $search,
        ));
        $company = TPS_Customers_Model::count_customers(array(
            'type'   => 'company',
            'search' => $search,
        ));
        return array(
            'all' => '<a href="' . esc_url(remove_query_arg('type', $base)) . '" class="' . ( $current === '' ? 'current' : '' ) . '">All <span class="count">(' . (int) $all . ')</span></a>',
            'individual' => '<a href="' . esc_url(add_query_arg('type', 'individual', $base)) . '" class="' . ( $current === 'individual' ? 'current' : '' ) . '">Individual <span class="count">(' . (int) $individual . ')</span></a>',
            'company' => '<a href="' . esc_url(add_query_arg('type', 'company', $base)) . '" class="' . ( $current === 'company' ? 'current' : '' ) . '">Company <span class="count">(' . (int) $company . ')</span></a>',
        );
    }


    // Prepara itens (paginação global + filtros)
    public function prepare_items()
    {

        $current_page = $this->get_pagenum();
        $per_page     = (int) tps_get_per_page();
        $type   = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
        $order   = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';
        $args = array(
            'type'     => $type,
            'search'   => $search,
            'orderby'  => $orderby,
            'order'    => $order,
            'per_page' => $per_page,
            'offset'   => ( $current_page - 1 ) * $per_page,
        );
        $this->items = TPS_Customers_Model::get_customers($args);
        $total_items = TPS_Customers_Model::count_customers(array(
            'type'   => $type,
            'search' => $search,
        ));
        $this->set_pagination_args(array(
            'total_items' => (int) $total_items,
            'per_page'    => (int) $per_page,
            'total_pages' => (int) ceil($total_items / $per_page),
        ));
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }
}
