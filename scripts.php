<?php 

function client_showcase_load_scripts() {
  wp_enqueue_script( 'jquery-ui-sortable' );
  wp_enqueue_script( 'update_order',  plugins_url('js/update-order.js', __FILE__) );
  wp_enqueue_style( 'client-showcase-admin',  plugins_url('css/admin.css', __FILE__) );
  wp_enqueue_style( 'client-showcase-bootstrap-css', plugins_url('css/bootstrap.min.css', __FILE__), '3.3.5');
}
