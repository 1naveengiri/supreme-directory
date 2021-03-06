<?php
/**
 * Functions for the Supreme Directory child theme
 *
 * This file includes functions for the Supreme Directory child theme.
 *
 * @since 0.0.1
 * @package Supreme_Directory
 */


/*#############################################
SUPREME DIRECTORY CODE STARTS
#############################################*/

/*
 * Define some constants for later use.
 */
if (!defined('SD_DEFAULT_FEATURED_IMAGE')) define('SD_DEFAULT_FEATURED_IMAGE', get_stylesheet_directory_uri() . "/images/featured.jpg");
if (!defined('SD_VERSION')) define('SD_VERSION', "2.1.0.2");
if (!defined('SD_CHILD')) define('SD_CHILD', 'supreme-directory');

if(is_admin()){
    require_once('inc/sd-class-metabox.php');             // Add settings for the featured area
}

// configs
require_once('inc/config.php');

/**
 * Adds GeoDirectory plugin required admin notice.
 *
 * @since 1.0.0
 */
function geodir_sd_force_update_remove_notice()
{

    $screen = get_current_screen();
    if(isset($screen->id) && $screen->id=='themes'){
    $action = 'install-plugin';
    $slug = 'geodirectory';
    $install_url = wp_nonce_url(
        add_query_arg(
            array(
                'action' => $action,
                'plugin' => $slug
            ),
            admin_url( 'update.php' )
        ),
        $action.'_'.$slug
    );

    $message = sprintf( __("This theme was designed to work with the GeoDirectory plugin! <a href='%s' >Click here to install it.</a>", 'supreme-directory'), esc_url($install_url) ) ;

    printf('<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message);
    }

}


/**
 * Add body classes to the HTML where needed.
 *
 * @since 0.0.1
 * @param array $classes The array of body classes.
 * @return array The array of body classes.
 */
function sd_custom_body_class($classes)
{
    $classes[] = 'sd-common';

    // add 'sd' to the default autogenerated classes, for this we need to modify the $classes array.
    $classes[] = 'sd';

    if(get_theme_mod('dt_blog_sidebar_position', DT_BLOG_SIDEBAR_POSITION) == 'right'){
        $classes[] = 'sd-right-sidebar';
    }else{
        $classes[] = 'sd-left-sidebar';

    }

    // return the modified $classes array
    return $classes;
}
add_filter('body_class', 'sd_custom_body_class');

/*
 * Throw admin notice and stop loading the theme if GeoDirectory plugin is not installed.
 */
if (!defined('GEODIRECTORY_VERSION')) {
    add_action('admin_notices', 'geodir_sd_force_update_remove_notice');
   // return;
}else{
    include_once('inc/geodirectory-compatibility.php');
}

/**
 * Adds the CSS and JS for the theme.
 *
 * @since 1.0.0
 */
function sd_enqueue_styles()
{
    ob_start();
    sd_theme_customize_css();
    $customizer_css = ob_get_clean();

    wp_add_inline_style( 'directory-theme-child-style', $customizer_css );
}
add_action('wp_enqueue_scripts', 'sd_enqueue_styles');

function sd_theme_customize_css() {
    do_action( 'sd_theme_customize_css' );
}

/**
 * Loads the translation files for WordPress.
 *
 * @since 1.0.0
 */
function sd_theme_setup()
{
    load_child_theme_textdomain( SD_CHILD, get_stylesheet_directory() . '/languages' );
    add_filter('tiny_mce_before_init','sd_theme_editor_dynamic_styles',11,1);
    remove_action( 'dt_footer_copyright', 'dt_footer_copyright_default', 10 );
}

add_action('after_setup_theme', 'sd_theme_setup');

/**
 * Add dynamic styles to the WYSIWYG editor.
 *
 * @param $mceInit
 * @since 1.1.0
 * @return mixed
 */
function sd_theme_editor_dynamic_styles( $mceInit ) {
    ob_start();
    ?>
    body.mce-content-body {
    font-size: 15px;
    }
    <?php
    $styles = preg_replace( "/\r|\n/", " ", ob_get_clean()); // seems to need line breaks removed
    if ( isset( $mceInit['content_style'] ) ) {
        $mceInit['content_style'] .= ' ' . $styles . ' ';
    } else {
        $mceInit['content_style'] = $styles . ' ';
    }

    return $mceInit;
}


/*################################
      BLOG FUNCTONS
##################################*/

/**
 * Redesign entry metas for blog entries template.
 *
 * @since 1.0.0
 */
function supreme_entry_meta()
{
    if (is_sticky() && is_home() && !is_paged()) {
        printf('<span class="sticky-post">%s</span>', __('Featured', 'supreme-directory'));
    }

    $format = get_post_format();
    if (current_theme_supports('post-formats', $format)) {
        printf('<span class="entry-format">%1$s<a href="%2$s">%3$s</a></span>',
            sprintf('<span class="screen-reader-text">%s </span>', _x('Format', 'Used before post format.', 'supreme-directory')),
            esc_url(get_post_format_link($format)),
            get_post_format_string($format)
        );
    }

    if (in_array(get_post_type(), array('post', 'attachment'))) {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

        $time_string = sprintf($time_string,
            esc_attr(get_the_date('c')),
            get_the_date(),
            esc_attr(get_the_modified_date('c')),
            get_the_modified_date()
        );

        printf('<span class="posted-on"><span class="screen-reader-text">%1$s </span><a href="%2$s" rel="bookmark">%3$s</a></span>',
            _x('Posted on', 'Used before publish date.', 'supreme-directory'),
            esc_url(get_permalink()),
            $time_string
        );
    }

    if ('post' == get_post_type()) {
        $display_author = apply_filters('sd_entry_meta_display_author', is_singular() || is_multi_author());
        if ($display_author) {
            printf('<span class="byline"><span class="author vcard"><span class="screen-reader-text">%1$s </span><a class="url fn n" href="%2$s">%3$s</a></span></span>',
                _x('Author', 'Used before post author name.', 'supreme-directory'),
                esc_url(get_author_posts_url(get_the_author_meta('ID'))),
                get_the_author()
            );
        }

        $categories_list = get_the_category_list(_x(', ', 'Used between list items, there is a space after the comma.', 'supreme-directory'));
        if ($categories_list) {
            printf('<span class="cat-links"><span class="screen-reader-text">%1$s </span>%2$s</span>',
                _x('Categories', 'Used before category names.', 'supreme-directory'),
                $categories_list
            );
        }

        $tags_list = get_the_tag_list('', _x(', ', 'Used between list items, there is a space after the comma.', 'supreme-directory'));
        if ($tags_list) {
            printf('<span class="tags-links"><span class="screen-reader-text">%1$s </span>%2$s</span>',
                _x('Tags', 'Used before tag names.', 'supreme-directory'),
                $tags_list
            );
        }
    }

}



/**
 * Runs on theme activation.
 *
 * @since 1.0.0
 */
function sd_theme_activation()
{
    //set the theme mod heights/settings
    sd_set_theme_mods();
    // add some page and set them if default settings set
    //sd_activation_install(); @todo we can't add info in install, add as an option
}

add_action('after_switch_theme', 'sd_theme_activation');

/**
 * Sets the default setting for the theme.
 *
 * @since 1.0.0
 */
function sd_set_theme_mods()
{

    $ds_theme_mods = get_theme_mods();
    if (!empty($ds_theme_mods) && !get_option('ds_theme_mod_backup')) {
        update_option('ds_theme_mod_backup', $ds_theme_mods);
    }

    $sd_theme_mods = array(
        "dt_header_height" => "61px",
        "dt_p_nav_height" => "61px",
        "dt_p_nav_line_height" => "61px",
        "logo" => get_stylesheet_directory_uri() . "/images/logo.png"
    );

    /**
     * Parse incoming $args into an array and merge it with defaults
     */
    $sd_theme_mods = wp_parse_args($ds_theme_mods, $sd_theme_mods);

    foreach ($sd_theme_mods as $key => $val) {
        set_theme_mod($key, $val);
    }

}

/**
 * Sets the default settings if they are not already set.
 *
 * @since 1.0.0
 */
function sd_activation_install()
{
    // if Hello World post is not published then we bail
    if (get_post_status(1) != 'publish' || get_the_title(1)!=__('Hello world!','supreme-directory')) {
        return;
    }

    // Use a static front page
    //delete_option('sd-installed'); // @todo remove, only for testing
    $is_installed = get_option('sd-installed');
    if (!$is_installed) {
        // install pages

        // Insert the home page into the database if not exists
        $home = get_page_by_title('Find Local Treasures!');
        if (!$home) {
            // Set the home page
            $sd_home = array(
                'post_title' => __('Find Local Treasures!', 'supreme-directory'),
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            );
            $home_page_id = wp_insert_post($sd_home);
            if ($home_page_id) {
                update_post_meta($home_page_id, 'subtitle', __('Discover the best places to stay, eat, shop and events near you.', 'supreme-directory'));
                update_option('page_on_front', $home_page_id);
                update_option('show_on_front', 'page');
            }
        }

        // Insert the blog page into the database if not exists
        $blog = get_page_by_title('Blog');
        if (!$blog) {
            // Set the blog page
            $sd_blog = array(
                'post_title' => __('Blog', 'supreme-directory'),
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            );
            $blog_page_id = wp_insert_post($sd_blog);
            if ($blog_page_id) {
                update_option('page_for_posts', $blog_page_id);
            }
        }


        // remove some widgets for clean look
        $sidebars_widgets = get_option('sidebars_widgets');
        if (isset($sidebars_widgets['geodir_listing_top'])) {
            $sidebars_widgets['geodir_listing_top'] = array();
        }
        if (isset($sidebars_widgets['geodir_search_top'])) {
            $sidebars_widgets['geodir_search_top'] = array();
        }
        if (isset($sidebars_widgets['geodir_detail_sidebar'])) {
            $sidebars_widgets['geodir_detail_sidebar'] = array();
        }
        update_option('sidebars_widgets', $sidebars_widgets);


        // set the menu if it does not exist
        // Check if the menu exists
        $menu_name = 'SD Menu';
        $menu_exists = wp_get_nav_menu_object( $menu_name );

        // If it doesn't exist, let's create it.
        if( !$menu_exists){
            $menu_id = wp_create_nav_menu($menu_name);

            // Set up default menu items
            $home_page_menu_id = (isset($home_page_id)) ? $home_page_id : $home->ID;


            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' =>  __('Home','supreme-directory'),
                'menu-item-classes' => 'home',
                'menu-item-object' => 'page',
                'menu-item-object-id' => $home_page_menu_id,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'));

            $blog_page_menu_id = (isset($blog_page_id)) ? $blog_page_id : $blog->ID;
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' =>  __('Blog','supreme-directory'),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $blog_page_menu_id,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'));


            // if no primary-menu is set then set one
            if ( $menu_id && !has_nav_menu( 'primary-menu' ) ) {
                set_theme_mod( 'nav_menu_locations', array('primary-menu' => $menu_id));
                update_option('geodir_theme_location_nav',array('primary-menu'));
                set_theme_mod('dt_logo_margin_top', '20px');
            }

        }


        // set the map pin to bounce on listing hover on listings pages
        update_option('geodir_listing_hover_bounce_map_pin', 1);
        // set the advanced paging to show on listings pages
        update_option('geodir_pagination_advance_info', 'before');
        // set the details page to use list and not tabs
        update_option('geodir_disable_tabs', '1');
        // disable some details page tabs that we show in the sidebar
        update_option('geodir_detail_page_tabs_excluded', array('post_images','post_map','related_listing'));
        // Set the installed flag
        update_option('sd-installed', true);

    }

}


//Remove Header Top from directory starter
function sd_dt_remove_header_top_from_customizer( $wp_customize ) {
    $wp_customize->remove_section( 'dt_header_top_section' );
}
add_action( 'customize_register', 'sd_dt_remove_header_top_from_customizer', 20);

function sd_dt_enable_header_top_return_zero() {
    return "0";
}
add_filter('theme_mod_dt_enable_header_top', 'sd_dt_enable_header_top_return_zero');




add_filter( 'body_class', 'sd_remove_bp_home_class', 10, 2 );
function sd_remove_bp_home_class( $wp_classes, $extra_classes ) {

    if (class_exists('BuddyPress') && bp_is_group_home()) {
        $wp_classes = array_diff($wp_classes, array('home'));
    }

    return $wp_classes;
}

/**
 * Add the title and subtitle to the page feature area.
 *
 * @since 1.0.4
 */
function sd_feature_area_title_meta(){
    global $pid;
    $title_class = "text-white display-3";
    $pid = $pid ? $pid : get_the_ID();
    $subtitle = '';
    
    ob_start();
    if (is_category() || is_tag()) {
        $pid = '';
        ?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php single_cat_title(); ?></h1>
        <?php
    } elseif (is_singular()) {
        ?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php the_title(); ?></h1>
        <?php
    } elseif (function_exists('is_woocommerce') && is_woocommerce()) {
        ?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php woocommerce_page_title(); ?></h1>
        <?php
    } else if ( is_search() ) {
		?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php echo apply_filters( 'sd_featured_area_search_page_title', sprintf( __( 'Search Results for: %s', 'supreme-directory' ), '<span>' . get_search_query() . '</span>' ) ); ?></h1>
        <?php
    } else if ( !is_front_page() && is_home() ) {
        ?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php echo get_the_title( get_option('page_for_posts', true) ); ?></h1>
        <?php
    } elseif (is_archive()) {
        ?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php the_archive_title(); ?></h1>
        <?php
    } else {
        ?>
        <h1 class="entry-title <?php echo $title_class ;?>"><?php the_title(); ?></h1>
        <?php
    }
    
    $title = ob_get_clean();
    echo apply_filters('sd_featured_area_title', $title);
    
	if ( is_search() ) {
		$subtitle = '';
	} elseif( $pid ) {
		$subtitle = get_post_meta($pid , 'subtitle', true);
	}

    $subtitle = apply_filters('sd_featured_area_subtitle',$subtitle);
    if ($subtitle) {
        echo '<div class="entry-subtitle text-white h5">' . $subtitle . '</div>'; // not escaped by design to allow users to add html here if required
    }
}
add_action('sd_feature_area','sd_feature_area_title_meta',10);

function sd_add_sd_home_class($classes) {
    global $post;
    //echo '###'.get_option('show_on_front');
    if (is_front_page()) {
        $classes[] = 'sd-homepage';
        if(get_option('show_on_front')!='posts'){
            $classes[] = 'sd-float-menu';
        }
    }

    // check for location page
    if(function_exists('geodir_is_page') && geodir_is_page('location')){
        $featured_type  = get_post_meta($post->ID, '_sd_featured_area', true);
        if(!$featured_type) {
            $classes[] = 'sd-float-menu';
        }
    }

    return $classes;
}
add_filter( 'body_class', 'sd_add_sd_home_class' );

function sd_feature_area(){

    if (is_front_page()) {
        echo '<div class="home-more h2"  id="sd-home-scroll" ><a href="#sd-home-scroll" class="text-white" aria-label="' . esc_attr__( 'Main Content', 'supreme-directory' ) . '"><i class="fas fa-chevron-down"></i></a></div>';
    }
}
add_action('sd_feature_area','sd_feature_area',15);

/**
 * Output the author page content and allow it to be filtered.
 *
 * @since 1.0.82
 * @param Object $author The author object.
 */
function sd_author_content_output($author){
    while (have_posts()) : the_post();

        // Include the page content template.
        get_template_part('content-blog');

        // End the loop.
    endwhile;

    // Previous/next page navigation.
    the_posts_pagination(array(
        'prev_text' => __('Previous', 'supreme-directory'),
        'next_text' => __('Next', 'supreme-directory'),
    ));
}

add_action('sd_author_content','sd_author_content_output',10,1);


/**
 * Get the current page URL.
 *
 * @since 1.1.4
 *
 * @return string The current URL.
 */
function sd_current_page_url() {
    $current_url = 'http';
    if ( isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' ) {
        $current_url .= 's';
    }
    $current_url .= "://";

    /*
     * Since we are assigning the URI from the server variables, we first need
     * to determine if we are running on apache or IIS.  If PHP_SELF and REQUEST_URI
     * are present, we will assume we are running on apache.
     */
    if ( !empty( $_SERVER['PHP_SELF'] ) && !empty( $_SERVER['REQUEST_URI'] ) ) {
        // To build the entire URI we need to prepend the protocol, and the http host
        // to the URI string.
        $current_url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    } else {
        /*
         * Since we do not have REQUEST_URI to work with, we will assume we are
         * running on IIS and will therefore need to work some magic with the SCRIPT_NAME and
         * QUERY_STRING environment variables.
         *
         * IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
         */
        $current_url .= $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

        // If the query string exists append it to the URI string
        if ( isset( $_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
    }

    return apply_filters( 'sd_current_page_url', $current_url );
}

// UsersWP compatibility
add_filter('get_post_metadata', 'sd_hide_banner_on_uwp_pages', 10, 4);
/**
 * Hides banners on UsersWP pages.
 *
 * @since 1.1.7
 *
 * @param null|array|string $metadata     The value get_metadata() should return - a single metadata value,
 *                                     or an array of values.
 * @param int               $object_id Object ID.
 * @param string            $meta_key  Meta key.
 * @param bool              $single    Whether to return only the first value of the specified $meta_key.
 *
 * @return string Meta value.
 */
function sd_hide_banner_on_uwp_pages($metadata, $object_id, $meta_key, $single) {
    if (defined('USERSWP_VERSION') && is_uwp_page()) {
        if ($meta_key == 'sd_remove_head' && $single) {
            $metadata = "1";
        }
    }
    return $metadata;
}

/**
 * Change copyright texts
 */
function sd_footer_copyright_default() {
    $dt_disable_footer_credits = esc_attr(get_theme_mod('dt_disable_footer_credits', DT_DISABLE_FOOTER_CREDITS));
    if ($dt_disable_footer_credits != '1') {
        $theme_name = "Supreme Directory";
        $theme_url = "https://wordpress.org/themes/supreme-directory/";

        $wp_link = '<a href="https://wordpress.org/" target="_blank" title="' . esc_attr__('WordPress', 'supreme-directory') . '"><span>' . __('WordPress', 'supreme-directory') . '</span></a>';
        $default_footer_value = sprintf(__('Copyright &copy; %1$s %2$s %3$s Theme %4$s', 'supreme-directory'),date('Y'),"<a href='$theme_url' target='_blank' title='$theme_name'>", $theme_name, "</a>");
        $default_footer_value .= sprintf(__(' - Powered by %s.', 'supreme-directory'), $wp_link);

        echo $default_footer_value;

    }else{
        echo esc_attr( get_theme_mod( 'dt_copyright_text', DT_COPYRIGHT_TEXT ) );
    }
}
add_action( 'dt_footer_copyright', 'sd_footer_copyright_default', 10 );

/**
 * Change sidebar widget classes.
 *
 * @param $class
 *
 * @return mixed
 */
function sd_sidebar_widget_class($class){

    $class = str_replace(" p-3 "," pb-3 px-3 ",$class);

    return $class;
}
add_filter('ds_sidebar_widget_class','sd_sidebar_widget_class');

function sd_header_extra_class( $class ){

    if( is_front_page() ){
        $class .= ' z-index-1 position-absolute w-100 bg-transparent ';
    }

    return $class;
}
add_filter('dt_header_extra_class','sd_header_extra_class');

add_action( 'dt_css', 'sd_css' );

function sd_css(){
    if(0){ ?><style><?php } ?>
.featured-area .gd-categories-widget .geodir-cat-list-tax{
    display: none;
}
		<?php if(0){ ?></style><?php }
}