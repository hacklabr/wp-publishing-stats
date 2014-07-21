<?php
/*
* Plugin Name: Publishing Stats
* Plugin URI: https://github.com/hacklabr/wp-publishing-stats
* Description: User publishing statistics
* Version: 0.3
* Author: Leo Germani, Vinicius Massuchetto
* Author URI: http://github.com/hacklabr/wp-publishing-stats
*/
class Publishing_Stats {

    var $dir;
    var $order_by_field;
    var $sort_order;

    function __construct() {
        load_plugin_textdomain( 'ps', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

        $this->dir = dirname( __FILE__ );
        $this->url = plugins_url( false, __FILE__ );
        
        $this->order_by_field = filter_input( INPUT_GET, "orderby", FILTER_DEFAULT, array('options' => array("default" => "display_name")) );
        $this->sort_order = filter_input( INPUT_GET, "sort", FILTER_DEFAULT, array('options' => array("default" => "asc")) ) ;

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    function admin_enqueue_scripts() {
        $lang = str_replace('_', '-', get_locale());
        $data = array();

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui' );
        wp_enqueue_script( 'jquery-flot', $this->url . '/js/jquery.flot.min.js' );
        wp_enqueue_script( 'jquery-flot-time', $this->url . '/js/jquery.flot.time.min.js' );
        wp_enqueue_script( 'jquery-ui-datepicker' );

        wp_enqueue_script( 'jquery-ui-datepicker-i18n', "http://jquery-ui.googlecode.com/svn/tags/latest/ui/i18n/jquery.ui.datepicker-$lang.js" );
        $data['lang'] = $lang;

        wp_enqueue_style( 'jquery-datepicker-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
        wp_enqueue_script( 'publishing-stats', $this->url . '/js/ps.js', array( 'jquery-flot', 'jquery-flot-time', 'jquery-ui-datepicker' ), false, true );
        wp_localize_script( 'publishing-stats', 'psDatepicker', $data );
    }

    function admin_menu() {
        $page_hook_suffix = add_submenu_page( 'options-general.php', __( 'Publishing Stats', 'ps' ), __( 'Publishing Stats', 'ps' ),
            'manage_options', 'ps', array( $this, 'submenu_page' ) );

        add_action('admin_print_scripts-' . $page_hook_suffix, array( $this, 'admin_enqueue_scripts' ) );
    }

    function submenu_page() {
        
        global $wpdb;

        // Fetching

        $time_end_filtered = filter_input( INPUT_GET, 'time_end' );
        $time_ini_filtered = filter_input( INPUT_GET,'time_ini' );
        
        $time_ini = !empty( $time_end_filtered )
            ? date( 'Y-m-d', strtotime( $time_ini_filtered ) )
            : date( 'Y-m-d', current_time( 'timestamp' ) - 3600 * 24 * 30 );
        $time_end = !empty( $time_ini_filtered )
            ? date( 'Y-m-d', strtotime( $time_end_filtered ) )
            : date( 'Y-m-d', current_time( 'timestamp' ) );
        $time_end_query = date( 'Y-m-d', strtotime( $time_end ) + 24 * 3600 );

        $where = $wpdb->prepare(" WHERE 1=1
            AND post_status = 'publish'
            AND post_date >= '%s'
            AND post_date <= '%s'
        ", $time_ini, $time_end_query );

        $post_types = apply_filters( 'ps_post_types', array( 'post' ) );
        $where .= " AND post_type IN ('" . implode( ',', $post_types ) . "') ";
        
        $role = filter_input( INPUT_GET, 'role' );
        if ( $role != "" ) {
            $users = get_users_by_role( $role, 10000 );
            $user_id_list = array();
            foreach ( $users as $user ) {
                $user_id_list[] = $user->ID;
            }
            
            $where .= " AND post_author in ( " . implode(",", $user_id_list) . ") ";
        }
        
        $sql = "SELECT ID, post_date, post_title, post_author FROM {$wpdb->posts} $where";
        $posts = $wpdb->get_results( $sql );

        // Formating

        $_plotdata = array();
        $userdata = array();
        $totals = array(
            'users' => 0,
            'days' => array(),
            'posts' => 0
        );
        foreach( $posts as $p ) {
            
            $k = strtotime( date( 'Y-m-d', strtotime( $p->post_date . ' GMT' ) ) );
            $_plotdata[ $k ] = !empty( $_plotdata[ $k ] ) ? $_plotdata[ $k ] + 1 : 1;

            // Consider this author in the final statistic or not
            if ( !$author_id = apply_filters( 'ps_author', $p->post_author ) ) {
                continue;
            }

            if ( empty( $userdata[ $author_id ] ) ) {
                $userdata[ $author_id ] = array(
                    'display_name' => get_the_author_meta( 'display_name', $author_id ),
                    'posts' => array(),
                    'post_count' => 0
                );
            }
            
            $userdata[ $author_id ]['posts'][] = $p->ID;
            $userdata[ $author_id ]['post_count']++;

            $totals['days'][ $k ] = 1;
            $totals['posts']++;

        }

        $totals['users'] = count( $userdata );
        $totals['days'] = count( $totals['days'] );
        $totals['posts_per_day'] = number_format( $totals['posts'] / $totals['days'], 2 );
        foreach( $userdata as &$u ) {
            $u['posts_per_day'] = number_format( $u['post_count'] / $totals['days'], 2 );
        }
        
        // Sorting

        ksort( $_plotdata );
        usort( $userdata, array( $this, 'userdata_sort' ) );
        foreach( $userdata as $k => $v ) {
            ksort( $v['posts'] );
        }

        // JavaScript conversion

        $plotdata = array();
        foreach( $_plotdata as $k => $v ) {
            $plotdata[] = array($k * 1000, $v);
        }
        $plotdata = json_encode($plotdata);
        
        wp_localize_script( 'publishing-stats', 'ps', array( 'plotdata' => $plotdata ) );

        // Load view
        
        $base_class_sortable = "manage-column column-title sortable ";        
        $class_column_display_name = $class_column_post_count = $class_column_posts_per_day = $base_class_sortable;
        
        if ( $this->order_by_field ) {
            $display_name_sort = "asc";
            $post_count_sort = "asc";
            $posts_per_day_sort = "asc";

            switch($this->order_by_field)
            {
                case "display_name":                    
                    $display_name_sort = ( $this->sort_order == "asc" ? "desc" : "asc" ) ;
                    break;
                case "post_count":
                    $post_count_sort = ( $this->sort_order == "asc" ? "desc" : "asc" ) ;
                    break;
                case "posts_per_day":
                    $posts_per_day_sort = ( $this->sort_order == "asc" ? "desc" : "asc" ) ;
                    break;

            }
        }
        
        $orderby = $this->order_by_field;
        $sort = $this->sort_order;

        include( $this->dir . '/ps-stats.php' );
    }

    function userdata_sort( $a, $b ) {
        
        $sort_order = 1;
        
        if ( $this->sort_order == "desc"  ) {
            $sort_order = -1;
        }
                
        return $a[$this->order_by_field] > $b[$this->order_by_field] ? 1 * $sort_order : -1 * $sort_order;
    }
    
    function get_user_posts() {
        $ids = explode(',', filter_input(INPUT_GET, 'ids', FILTER_SANITIZE_STRING));
        $html = '';
        
        foreach ($ids as $id) {
            $p = get_post($id);
            $html .= '<li><a href="' . get_permalink( $p->ID ) . '">' . $p->post_title . '</a> (' . $p->post_date . ')</li>';
        }
        
        echo $html;
        die();
    }
}

function ps_init() {
    new Publishing_Stats();
}
add_action( 'plugins_loaded', 'ps_init' );
add_action( 'wp_ajax_ps_get_user_posts', array('Publishing_Stats', 'get_user_posts'));
