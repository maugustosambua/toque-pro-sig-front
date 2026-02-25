<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Garante model carregado
require_once TPS_PLUGIN_PATH . 'modules/documents/class-documents-model.php';

class TPS_Documents_List_Table extends WP_List_Table {

    // Inicializa a tabela
    public function __construct() {
        parent::__construct( array(
            'singular' => 'document',
            'plural'   => 'documents',
            'ajax'     => false,
        ) );
    }

    // Define colunas
    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'number'     => 'Number',
            'type'       => 'Type',
            'customer'   => 'Customer',
            'status'     => 'Status',
            'issue_date' => 'Issue Date',
            'subtotal'   => 'Subtotal',
            'iva'        => 'IVA',
            'total'      => 'Total',
        );
    }
    

    // Colunas ordenáveis
    protected function get_sortable_columns() {
        return array(
            'number'     => array( 'number', false ),
            'type'       => array( 'type', false ),
            'issue_date' => array( 'issue_date', false ),
        );
    }

    // Checkbox
    protected function column_cb( $item ) {
        return '<input type="checkbox" name="document_id[]" value="' . esc_attr( $item->id ) . '">';
    }

    // Renderiza colunas especiais
    protected function column_default( $item, $column_name ) {

        // Detalhes na lista
        if ( in_array( $column_name, array( 'subtotal', 'iva', 'total' ), true ) ) {

            $totals = TPS_Document_Lines_Model::totals( $item->id );

            if ( $column_name === 'subtotal' ) {
                return number_format( $totals['subtotal'], 2 );
            }

            if ( $column_name === 'iva' ) {
                return number_format( $totals['iva'], 2 );
            }

            if ( $column_name === 'total' ) {
                return number_format( $totals['total'], 2 );
            }
        }

        // Badge do status
        if ( $column_name === 'status' ) {

            $status = $item->status;

            $labels = array(
                'draft'     => 'Draft',
                'issued'    => 'Issued',
                'cancelled' => 'Cancelled',
            );

            $styles = array(
                'draft'     => 'background:#fff8e5;color:#8a6d00;border:1px solid #f1d48b;',
                'issued'    => 'background:#e7f7ed;color:#1f7a39;border:1px solid #9ad3ac;',
                'cancelled' => 'background:#fde8e8;color:#a61b1b;border:1px solid #f1a6a6;',
            );

            $label = $labels[ $status ] ?? ucfirst( $status );
            $style = $styles[ $status ] ?? 'background:#f1f1f1;color:#333;border:1px solid #ccc;';

            return '<span style="display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;' . esc_attr( $style ) . '">' . esc_html( $label ) . '</span>';
        }

        return $item->$column_name ?? '';
    }

    // Coluna number com acções
    protected function column_number( $item ) {

        $edit_url = admin_url( 'admin.php?page=tps-documents-add&document_id=' . $item->id );

        $actions = array(
            'edit' => '<a href="' . esc_url( $edit_url ) . '">Edit</a>',
        );

        // Mostra Issue só quando draft
        if ( $item->status === 'draft' ) {

            $issue_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=tps_issue_document&document_id=' . $item->id ),
                'tps_issue_document'
            );

            $actions['issue'] = '<a href="' . esc_url( $issue_url ) . '">Issue</a>';
        }

        //Mostra Cancel só quando issued
        if ( $item->status === 'issued' ) {

            $cancel_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=tps_cancel_document&document_id=' . $item->id ),
                'tps_cancel_document'
            );
        
            $actions['cancel'] = '<a href="' . esc_url( $cancel_url ) . '" onclick="return confirm(\'Cancel this document?\');">Cancel</a>';
        }
        

        return sprintf(
            '<strong><a href="%s">%s</a></strong> %s',
            esc_url( $edit_url ),
            esc_html( $item->number ),
            $this->row_actions( $actions )
        );
    }

    // Prepara dados da tabela
    public function prepare_items() {

        // Cabeçalhos
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        // Reset de paginação ao aplicar filtro
        if ( isset( $_GET['filter_action'] ) ) {
            $_GET['paged'] = 1;
        }

        // Página actual
        $current_page = $this->get_pagenum();

        // Itens por página
        $per_page = tps_get_per_page();

        // Ordenação
        $orderby = $_GET['orderby'] ?? 'number';
        $order   = $_GET['order'] ?? 'DESC';

        // Filtros activos
        $type   = $_GET['doc_type'] ?? '';
        $status = $_GET['status'] ?? '';

        // Argumentos comuns
        $args = array(
            'orderby'  => $orderby,
            'order'    => $order,
            'per_page' => $per_page,
            'offset'   => ( $current_page - 1 ) * $per_page,
            'type'     => $type,
            'status'   => $status,
        );

        // Dados
        $this->items = TPS_Documents_Model::get_documents( $args );

        // Total com os MESMOS filtros
        $total_items = TPS_Documents_Model::count_documents( array(
            'type'   => $type,
            'status' => $status,
        ) );     

        // Paginação
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }

    // Filtros acima da tabela
    protected function extra_tablenav( $which ) {

        if ( $which !== 'top' ) {
            return;
        }

        $current_type   = $_GET['doc_type'] ?? '';
        $current_status = $_GET['status'] ?? '';
        $types          = TPS_Documents_Model::types();

        ?>

        <div class="alignleft actions">

            <select name="doc_type">
                <option value="">All types</option>
                <?php foreach ( $types as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_type, $key ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">All status</option>
                <option value="draft" <?php selected( $current_status, 'draft' ); ?>>Draft</option>
                <option value="issued" <?php selected( $current_status, 'issued' ); ?>>Issued</option>
                <option value="cancelled" <?php selected( $current_status, 'cancelled' ); ?>>Cancelled</option>
            </select>

            <?php submit_button( 'Filter', '', 'filter_action', false ); ?>

        </div>

        <?php
    }


    // Links rápidos no topo (All, Draft, Issued, Cancelled)
    protected function get_views() {

        $current_status = $_GET['status'] ?? '';

        $base_url = admin_url( 'admin.php' );
        $base_url = add_query_arg( array( 'page' => 'tps-documents' ), $base_url );

        // Preserva doc_type
        $doc_type = $_GET['doc_type'] ?? '';
        if ( $doc_type ) {
            $base_url = add_query_arg( 'doc_type', $doc_type, $base_url );
        }

        $counts = TPS_Documents_Model::count_by_status();

        $views = array();

        $all_url = remove_query_arg( 'status', $base_url );
        $views['all'] = '<a href="' . esc_url( $all_url ) . '" class="' . ( $current_status === '' ? 'current' : '' ) . '">All <span class="count">(' . (int) $counts['all'] . ')</span></a>';

        $statuses = array(
            'draft'     => 'Draft',
            'issued'    => 'Issued',
            'cancelled' => 'Cancelled',
        );

        foreach ( $statuses as $key => $label ) {
            $url = add_query_arg( 'status', $key, $base_url );
            $views[ $key ] = '<a href="' . esc_url( $url ) . '" class="' . ( $current_status === $key ? 'current' : '' ) . '">' . esc_html( $label ) . ' <span class="count">(' . (int) ( $counts[ $key ] ?? 0 ) . ')</span></a>';
        }

        return $views;
    }


}
