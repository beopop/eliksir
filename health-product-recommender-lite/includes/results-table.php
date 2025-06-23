<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HPRL_Results_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'hprl_result',
                'plural'   => 'hprl_results',
                'ajax'     => false,
            )
        );
    }

    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'id'         => 'ID',
            'first_name' => 'Ime',
            'last_name'  => 'Prezime',
            'email'      => 'Email',
            'phone'      => 'Telefon',
            'birth_year' => 'Godina',
            'location'   => 'Mesto',
            'answers'    => 'Odgovori',
            'product_id' => 'Proizvod',
            'created_at' => 'Datum',
            'actions'    => 'Akcija',
        );
    }

    public function get_sortable_columns() {
        return array(
            'id'         => array( 'id', false ),
            'first_name' => array( 'first_name', false ),
            'last_name'  => array( 'last_name', false ),
            'email'      => array( 'email', false ),
            'phone'      => array( 'phone', false ),
            'birth_year' => array( 'birth_year', false ),
            'location'   => array( 'location', false ),
            'created_at' => array( 'created_at', true ),
        );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => 'Obriši',
        );
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="hprl_selected[]" value="%d" />', $item['id'] );
    }

    public function column_actions( $item ) {
        $url = wp_nonce_url( add_query_arg( array(
            'page'   => 'hprl-results',
            'action' => 'delete',
            'id'     => $item['id'],
        ), 'admin.php' ), 'bulk-' . $this->_args['plural'] );
        return sprintf( '<a href="%s" onclick="return confirm(\'Obrisati ovaj unos?\');">Obriši</a>', esc_url( $url ) );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'answers':
                $ans = maybe_unserialize( $item['answers'] );
                if ( ! is_array( $ans ) ) {
                    $ans = array( $ans );
                }
                return esc_html( implode( ',', $ans ) );
            case 'product_id':
                return esc_html( $item['product_title'] );
            default:
                return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
        }
    }

    public function prepare_items() {
        global $wpdb;

        $per_page = 20;
        $search   = isset( $_REQUEST['s'] ) ? trim( wp_unslash( $_REQUEST['s'] ) ) : '';
        $orderby  = ! empty( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'created_at';
        $order    = ( ! empty( $_REQUEST['order'] ) && strtolower( $_REQUEST['order'] ) === 'asc' ) ? 'ASC' : 'DESC';

        $sortable = $this->get_sortable_columns();
        if ( ! array_key_exists( $orderby, $sortable ) ) {
            $orderby = 'created_at';
        }

        $where  = '';
        if ( $search !== '' ) {
            $like  = '%' . $wpdb->esc_like( $search ) . '%';
            $where = "WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s";
        }

        if ( $where ) {
            $total_items = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM " . HPRL_TABLE . " $where",
                    $like,
                    $like,
                    $like
                )
            );
        } else {
            $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM " . HPRL_TABLE );
        }

        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        if ( $where ) {
            $sql = $wpdb->prepare(
                "SELECT * FROM " . HPRL_TABLE . " $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $like,
                $like,
                $like,
                $per_page,
                $offset
            );
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM " . HPRL_TABLE . " ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset );
        }

        $items = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $items as &$it ) {
            $it['product_title'] = $it['product_id'] ? get_the_title( $it['product_id'] ) : '';
        }

        $this->items = $items;

        $this->set_pagination_args( array(
            'total_items' => (int) $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }

    public function process_bulk_action() {
        if ( 'delete' === $this->current_action() ) {
            check_admin_referer( 'bulk-' . $this->_args['plural'] );
            global $wpdb;
            $ids = array();
            if ( ! empty( $_REQUEST['hprl_selected'] ) ) {
                $ids = array_map( 'intval', (array) $_REQUEST['hprl_selected'] );
            } elseif ( ! empty( $_GET['id'] ) ) {
                $ids[] = intval( $_GET['id'] );
            }
            foreach ( $ids as $id ) {
                if ( $id > 0 ) {
                    $wpdb->delete( HPRL_TABLE, array( 'id' => $id ) );
                }
            }
        }
    }
}
