<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Techriver_maplists_list extends WP_List_Table{
	protected $tablename; //Name of table you are going to use refer to contructor function
	protected $per_page; //Items per page. Set in the constructor function
	
	protected $columns; // Columns for the table set in the constructor function
	
	
	  public function __construct() {
 
        parent::__construct( [
            'singular' => __( 'List', 'sp' ), //singular name of the listed records
            'plural'   => __( 'Lists', 'sp' ), //plural name of the listed records
            'ajax'     => false //should this table support ajax?
 
        ] );
		global $wpdb;
		  
		  
		//Settings
		$this->tablename = $wpdb->prefix . 'techriver_maplists'; //Change this to the table name of your data
		$this->per_page = 10; //Change this to the number of items per page.
		
		  
		 $columns = array(
		 );
		
 
    }
	
	public static function get_data($per_page = 10, $page_number = 1) {
		global $wpdb;
		
		 $sql = "SELECT * FROM {$this->tablename}";
 
		 if ( ! empty( $_REQUEST['orderby'] ) ) {
		   $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
		   $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		 }

		 $sql .= " LIMIT $per_page";

		 $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		 $result = $wpdb->get_results( $sql, 'ARRAY_A' );

		 return $result;
	}
	
	public static function delete_data( $id ) {
		  global $wpdb;

		  $wpdb->delete(
		    "{$this->tablename}",
		    [ 'ID' => $id ],
		    [ '%d' ]
		  );
	}
	
	public static function record_count($tablename) {
  		global $wpdb;
 
  		$sql = "SELECT COUNT(*) FROM ".$tablename;
 
  		return $wpdb->get_var( $sql );
	}
	
	
	public function no_items() {
  		_e( 'No data avaliable.', 'sp' );
	}
	
	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'special':
			case 'city':
				return $item[ $column_name ];
			default:
				return $item[ $column_name ]; //Show the default val
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'sp_delete_customer' );

		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Name', 'sp' ),
			'address' => __( 'Address', 'sp' ),
			'city'    => __( 'City', 'sp' )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true ),
			'city' => array( 'city', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->per_page;
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count($this->tablename);

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_customers( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_customer' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_customer( absint( $_GET['customer'] ) );

				wp_redirect( esc_url( add_query_arg() ) );
				exit;
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_customer( $id );

			}

			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}
	}
}

$map_lists_list = new Techriver_maplists_list();
$map_lists_list->prepare_items();
$map_lists_list->display();
