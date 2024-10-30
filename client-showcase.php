<?php
/*
Plugin Name: Client Showcase
Description:  A WordPress plugin that displays client logo's in a list using a shortcode anywhere on the page and also has
a widget to be able to display smaller logos in the sidebar.
Version: 1.2.0
Author: Darren Ladner
Author URI: https://www.hyperdrivedesigns.com
Requires at least: 4.0
Text Domain: client-showcase
Domain Path: /languages
*/

class ClientShowcase {


	private $directory = '';
	private $singular_name = 'client';
	private $plural_name = 'clients';
	private $content_type_name = 'client_showcase';

	public function __construct() {

    global $client_showcase_settings_page;

    add_option( 'client_showcase_list' );
    add_option( 'client_showcase_list_display', 'horizontal' );

		if (is_admin()) {
			add_action('init', array($this,'add_content_type'));
			add_action('init', array($this,'check_flush_rewrite_rules'));
			add_action('add_meta_boxes', array($this,'add_meta_boxes_for_content_type'));

			add_action('save_post_' . $this->content_type_name, array($this,'save_custom_content_type'));
			add_action('display_content_type_meta', array($this,'display_additional_meta_data'));

      add_filter("manage_edit-client_showcase_columns", array($this,'showcase_edit_columns'));
      add_action("manage_posts_custom_column",  array($this,'showcase_custom_columns'));

      add_action('admin_menu', array($this,'client_showcase_options_page'));

      add_action('wp_ajax_client_showcase_update_order', array($this, 'client_showcase_save_order'));

			add_action('admin_notices', array($this, 'client_showcase_admin_notice'));
			add_action('admin_init', array($this, 'client_showcase_nag_ignore'));


		}
		else
		{
			add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles'));
			add_shortcode('showcase', array($this,'client_showcase_section'));
		}
	}

	function client_showcase_admin_notice() {
		global $current_user ;
	  $user_id = $current_user->ID;
		global $pagenow;
    if ( $pagenow == 'plugins.php' )
		{
	  	/* Check that the user hasn't already clicked to ignore the message */
			if ( ! get_user_meta($user_id, 'client_showcase_ignore_notice') )
			{
		        echo '<div class="updated"><p>';
		        printf(__('You have updated the Client Showcase plugin. We made some changes with the new features. Be sure to read over the new
						documentation located <a href="https://hyperdrivedesigns.com/free-plugins/client-showcase/">here</a> as there is a new settings
						page and new savings options. | <a href="%1$s">Hide Notice</a>'), '?client_showcase_nag_ignore=0');
		        echo "</p></div>";
			}
		}
	}

	function client_showcase_nag_ignore() {
		global $current_user;
	        $user_id = $current_user->ID;
	        /* If user clicks to ignore the notice, add that to their user meta */
	        if ( isset($_GET['client_showcase_nag_ignore']) && '0' == $_GET['client_showcase_nag_ignore'] )
					{
	             add_user_meta($user_id, 'client_showcase_ignore_notice', 'true', true);
					}
	}

	function save_custom_content_type() {
    global $post;

    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
      return $post_id;
    }
    else
    {
        if ( isset( $_POST['client_url'] ) && $_POST['client_url'] != '' )
        {
          update_post_meta($post->ID, "client_url", $_POST["client_url"]);
        }
				else
				{
					update_post_meta($post->ID, "client_url", '');
				}
    }
  }

	function client_showcase_section() {
        global $post;
        add_image_size( 'client_showcase_widget_size', 125, 125, false );
        $new_client_order = get_option( 'client_showcase_list' );
        $client_showcase_list_display = get_option( 'client_showcase_list_display');

        if ( $client_showcase_list_display == 'horizontal' )
        {
          ?>
            <style>
              #listStyle li {
                display: inline-block;
              }
            </style>
          <?php
        }
        else if ( $client_showcase_list_display == 'vertical' )
        {
          ?>
            <style>
              #listStyle li {
                display: block;
              }
            </style>
          <?php
        }
				global $wpdb;
				$wpdb->get_results( "SELECT * FROM wp_posts WHERE post_type = 'client_showcase'" );
				$client_showcase_count = $wpdb->num_rows;
        $args = array( 'post_type' => 'client_showcase', 'posts_per_page' => $client_showcase_count, 'orderby' => 'post__in', 'post__in' => $new_client_order );
        $loop = new WP_Query( $args );
        ?>
        <ul id="listStyle">
        <?php
        	while ( $loop->have_posts() ) : $loop->the_post();
                $custom = get_post_custom($post->ID);
                ?>
                <li>
									<?php
									echo the_title();
									if( isset($custom['client_url'][0]))
									{
										?>
                    <a href="<?php echo $client_url = $custom["client_url"][0]; ?>">
                        <?php print $logo = get_the_post_thumbnail($post->ID, 'client_showcase_widget_size'); ?>
                    </a>
										<?php
									}
									else
									{
										 print $logo = get_the_post_thumbnail($post->ID, 'client_showcase_widget_size');
									}
									?>
                </li>
                <?php
            endwhile;
        ?>
        </ul>
        <?php
    }

	public function check_flush_rewrite_rules(){
		$has_been_flushed = get_option($this->content_type_name . '_flush_rewrite_rules');

		if($has_been_flushed != true){
			flush_rewrite_rules(true);
			update_option($this->content_type_name . '_flush_rewrite_rules', true);
		}
	}


	public function enqueue_public_scripts_and_styles() {
		wp_enqueue_style('client-showcase-styles', plugins_url('css/client-showcase-public-styles.css', __FILE__));
	}

	public function add_content_type() {
	 	$labels = array(
           'name'               => ucwords($this->singular_name),
           'singular_name'      => ucwords($this->singular_name),
           'menu_name'          => ucwords($this->plural_name),
           'name_admin_bar'     => ucwords($this->singular_name),
           'add_new'            => 'Add New ' . ucwords($this->singular_name),
           'add_new_item'       => 'Add New ' . ucwords($this->singular_name),
           'new_item'           => 'New ' . ucwords($this->singular_name),
           'edit_item'          => 'Edit ' . ucwords($this->singular_name),
           'view_item'          => 'View ' . ucwords($this->plural_name),
           'all_items'          => 'All ' . ucwords($this->plural_name),
           'search_items'       => 'Search ' . ucwords($this->plural_name),
           'parent_item_colon'  => 'Parent ' . ucwords($this->plural_name) . ':',
           'not_found'          => 'No ' . ucwords($this->plural_name) . ' found.',
           'not_found_in_trash' => 'No ' . ucwords($this->plural_name) . ' found in Trash.',
       	);

       	$args = array(
           'labels'            => $labels,
           'public'            => true,
           'publicly_queryable'=> true,
           'show_ui'           => true,
           'show_in_nav'       => true,
           'query_var'         => true,
           'hierarchical'      => false,
           'supports'          => array('title','editor','thumbnail'),
           'has_archive'       => true,
           'menu_position'     => 20,
           'show_in_admin_bar' => true,
           'menu_icon'         => 'dashicons-admin-users'
       	);

		//register your content type
		register_post_type($this->content_type_name, $args);

	}

	public function add_meta_boxes_for_content_type() {

		add_meta_box(
			$this->singular_name . '_meta_box', //id
			ucwords($this->singular_name) . ' Information', //box name
			array($this,'display_function_for_content_type_meta_box'), //display function
			$this->content_type_name, //content type
			'normal', //context
			'default' //priority
		);

	}

	public function display_function_for_content_type_meta_box($post) {

		$client_url = get_post_meta($post->ID,'client_url', true);

		//set nonce
		wp_nonce_field($this->content_type_name . '_nonce', $this->content_type_name . '_nonce_field');

		?>
		<p>Enter your client's URL below</p>
		<div class="field-container">
			<label for="client_url">Client URL</label>
			<input type="text" name="client_url" id="client_url" value="<?php echo $client_url; ?>"/>
		</div>
		<?php
	}



  public function showcase_edit_columns($columns){
          $columns = array(
              "cb" => "<input type=\"checkbox\" />",
              "title" => "Showcase Name",
              "description" => "Description",
              "client_url" => "Client URL",
              "featured_image" => "Logo",
          );
          return $columns;
  }


  public function showcase_custom_columns($column){
          global $post;
          $custom = get_post_custom();
          add_image_size( 'client_showcase_widget_size', 50, 50, false );
          switch ($column)
          {
              case "description":
                  the_excerpt();
                  break;
              case "client_url":
                  echo $custom["client_url"][0];
                  break;
              case "featured_image":
                  echo get_the_post_thumbnail($post->ID, 'client_showcase_widget_size');
                  break;
          }
  }



  public function create_client_showcase_options_page() {
    global $client_showcase_list;
    ob_start();
    if('POST' == $_SERVER['REQUEST_METHOD'])
    {
      $client_showcase_list_display = $_POST['list_display'];
      update_option('client_showcase_list_display', $client_showcase_list_display);
    }
    ?>
    <div class="wrap">
      <h2><?php _e('Client Showcase Settings', 'client-showcase'); ?></h2>
      <p><?php _e('Choose how your would like your list to display. Note: the Save Changes Button is only for the display list option.', 'client-showcase'); ?></p>
      <div class="options-form">
        <form id="showcase-options-form" name="showcase-options-form" method="POST" action="">
          <label for="list_display">List Display (Horizontal or Vertical)</label>
          <select id="list_display" name="list_display">
            <option value="<?php echo get_option( 'client_showcase_list_display' ); ?>"><?php echo get_option( 'client_showcase_list_display' ); ?></option>
            <option value="horizontal">Horizontal</option>
            <option value="vertical">Vertical</option>
          </select>
          <input type="submit" class="button-primary" name="list-display-submit" value="<?php _e('Save Changes'); ?>" />
        </form>
        <hr>
      </div>
      <br/>
      <p><?php _e('Drag N Drop the order you would like the Client Showcases to appear on the website.', 'client-showcase'); ?></p>
      <table class="wp-list-table widefat fixed posts client-showcase-list">
        <thead style="background: #0073aa">
          <tr>
            <th style="color: #fff;"><?php _e('Name', 'client-showcase'); ?></th>
            <th style="color: #fff;"><?php _e('ID', 'client-showcase'); ?></th>
          </tr>
        </thead>
        <tfoot style="background: #0073aa">
          <tr>
            <th style="color: #fff;"><?php _e('Name', 'client-showcase'); ?></th>
            <th style="color: #fff;"><?php _e('ID', 'client-showcase'); ?></th>
          </tr>
        </tfoot>
        <tbody>
          <?php
          $new_client_order = get_option( 'client_showcase_list' );
          $new_client_order_number = sizeof($new_client_order);
          $newargs = array( 'post_type' => 'client_showcase', 'meta_key' => 'client_url' );
          global $wpdb;
          $wpdb->get_results( "SELECT * FROM wp_posts WHERE post_type = 'client_showcase'" );
          $client_showcase_count = $wpdb->num_rows;

          if ($client_showcase_count > $new_client_order_number)
          {
            echo '<div class="alert alert-danger" role="alert">';
            echo 'Looks like you added some new client/clients. TIME TO REDO YOUR LIST ORDER.';
            echo ' The list order is saved upon the Drag N Drop action.';
            echo 'So if you leave the page without making a Drag N Drop action, then the new client/clients will not be updated.';
            echo 'Once you moved at least one of your clients around and you want to get rid of the Red Box just refresh the page.';

            echo '</div>';
            global $post;
            $args = array( 'post_type' => 'client_showcase', 'posts_per_page' => $client_showcase_count, 'orderby' => 'post__in' );
            $loop = new WP_Query( $args );
              while ( $loop->have_posts() ) : $loop->the_post();
                ?>
                <tr id="list_items_<?php echo $post->ID;?>" class="list_item">
                  <td><?php echo the_title(); ?></td>
                  <td><?php echo $post->ID; ?></td>
                </tr>
                <?php
              endwhile;
          }
          else if ($client_showcase_count < $new_client_order_number)
          {
            echo '<div class="alert alert-danger" role="alert">';
            echo 'Looks like you deleted some client/clients. TIME TO REDO YOUR LIST ORDER.';
            echo 'If you leave the page without making a Drag N Drop action, then the new client/clients will not be updated.';
            echo '</div>';
            global $post;
            $args = array( 'post_type' => 'client_showcase', 'posts_per_page' => $client_showcase_count, 'orderby' => 'post__in' );
            $loop = new WP_Query( $args );
              while ( $loop->have_posts() ) : $loop->the_post();
                ?>
                <tr id="list_items_<?php echo $post->ID;?>" class="list_item">
                  <td><?php echo the_title(); ?></td>
                  <td><?php echo $post->ID; ?></td>
                </tr>
                <?php
              endwhile;
          }
          else
          {
            global $post;
            $args = array( 'post_type' => 'client_showcase', 'posts_per_page' => $client_showcase_count, 'orderby' => 'post__in', 'post__in' => $new_client_order );
            $loop = new WP_Query( $args );
            while ( $loop->have_posts() ) : $loop->the_post();
              ?>
              <tr id="list_items_<?php echo $post->ID;?>" class="list_item">
                <td><?php echo the_title(); ?></td>
                <td><?php echo $post->ID; ?></td>
              </tr>
              <?php
            endwhile;
          }
          ?>
        </tbody>
      </table>
      <hr>
  </div>
  <?php
  echo ob_get_clean();
  }

  public function client_showcase_options_page() {
    global $client_showcase_settings_page;

    $client_showcase_settings_page = add_options_page('Client Showcase Options', 'Client Showcase Options', 'manage_options', 'client_showcase_options.php', array($this, 'create_client_showcase_options_page'));

    add_action('admin_print_styles-'.$client_showcase_settings_page, array($this,'client_showcase_load_scripts'));
  }

  public function client_showcase_load_scripts() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script( 'update_order',  plugins_url('/js/update-order.js', __FILE__) );
    wp_enqueue_style( 'client-showcase-admin',  plugins_url('/css/client-showcase-admin-styles.css', __FILE__) );
    wp_enqueue_style( 'client-showcase-bootstrap-css', plugins_url('/css/bootstrap.min.css', __FILE__) );
  }


  public function client_showcase_save_order() {

    $list = $client_showcase_list;
    $new_order = $_POST['list_items'];

    // save the new order
    update_option('client_showcase_list', $new_order);
  }

}

$client_showcase = new ClientShowcase;

class Client_Showcase extends WP_Widget {

    function __construct() {
        parent::__construct('client_showcase', 'Client Showcase', array('description' => 'Displays your client showcase images'));
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }
        echo __( '', 'client_showcase' );
        $this->getClients();
        echo $args['after_widget'];
    }

    function getClients() {
        global $post;
        add_image_size( 'client_showcase_widget_size', 75, 75, false );
        $args = array( 'post_type' => 'client_showcase', 'meta_key' => 'client_url');
        $loop = new WP_Query( $args );
        ?>
        <ul id="listStyle">
        <?php
        while ( $loop->have_posts() ) : $loop->the_post();
                $custom = get_post_custom($post->ID);
                ?>
                <li>
                    <a href="<?php echo $client_url = $custom["client_url"][0]; ?>">
                        <?php print $logo = get_the_post_thumbnail($post->ID, 'client_showcase_widget_size'); ?>
                    </a>
                </li>
                <?php
            endwhile;
        ?>
        </ul>
        <?php
    }

    function form($instance) {
        $render_widget = (!empty($instance['render_widget'] ) ? $instance['render_widget'] : 'true' );
        $client_showcase =(!empty($instance['client_showcase'] ) ? $instance['client_showcase'] : 3);
        $widget_title = (!empty($instance['widget_title'] ) ? esc_attr($instance['widget_title'] ) : 'Client Showcase');
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('render_widget'); ?>">
                <?php echo 'Display Widget'; ?>
                <select id="<?php echo $this->get_field_id('render_widget'); ?>" name="<?php echo $this->get_field_name('render_widget'); ?>">
                    <option value="true" <?php selected($render_widget, 'true'); ?>>Yes</option>
                    <option value="false"<?php selected($render_widget, 'false'); ?>>No</option>
                </select>
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('widget_title'); ?>">
                <?php echo 'Widget Title:'; ?>
                <input type="text" id="<?php echo $this->get_field_name('widget_title'); ?>" value="<?php echo $widget_title; ?>" />
            </label>
        </p>


    <?php
    }
}

add_action('widgets_init', 'client_showcase_widget');

function client_showcase_widget() {
    register_widget('Client_Showcase');
}
