<?php
namespace SandersForPresidentLanding\Wordpress\Admin\Requests;

if(!class_exists('WP_List_Table')){
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
use WP_List_Table;

class RequestTable extends WP_List_Table {
  private $service;

  public function __construct() {
    parent::__construct(array(
      'singular' => 'Request',
      'plural' => 'Requests',
      'ajax' => false
    ));
    $this->service = new RequestService();
  }

  public function get_columns() {
    return array(
      'cb' => '<input type="checkbox" />',
      'request_organization' => 'Organization',
      'request_organizer' => 'Organizer',
      'request_url' => 'URL',
      'request_date' => 'Date'
    );
  }

  public function get_views() {
    return array(
      'all' => '<a href="#">All</a>',
      'trash' => '<a href="#">Trash</a>'
    );
  }

  public function column_cb($item) {
    return "<input type=\"checkbox\" name=\"request[]\" value=\"{$item->id}\" />";
  }

  public function column_request_title($item) {
    if (!true) {
      $title = "<strong>EXAMPLE</strong>";
    } else {
      $title = "EXAMPLE"; //$item->title;
    }
    $actions = array(
      'view' => "<a href=\"?page={$_REQUEST['page']}&action=view&post=XYZ\">View</a>"
    );
    return $title . $this->row_actions($actions, false);
  }

  public function column_request_organizer($item) {
    return "Hello world";
    $name = $item->from['name'];
    $email = "<a href=\"mailto:{$item->from['email']}\">{$item->from['email']}</a>";
    return $name . "<br/>" . $email;
  }

  public function column_request_url() {
    return 'foobar.forberniesanders.com';
  }

  public function column_request_date($item) {
    return "Now";
    return $item->getDate();
  }

  public function get_sortable_columns() {
    return array(
      'message_title' => array('message_title', false)
    );
  }

  public function get_bulk_actions() {
    return array(
      'delete' => 'Delete'
    );
  }

  public function prepare_items() {
    $this->items = array(1,3,4);
    return;
    $this->_column_headers = array($this->get_columns(), array(), array());
    $this->items = $this->service->getMessages();
    $this->set_pagination_args(array(
      'total_items' => count($this->items),
      'per_page' => 10
    ));
  }
}
