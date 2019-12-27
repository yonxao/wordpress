<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module{
    private $options;
    function __construct($id, $name, $options = array(), $icon='', $cache = true) {
        $this->id = $id;
        $this->name = $name;
        $this->options = $options;
        $this->icon = $icon;
        $this->is_cache = $cache;
        add_action( 'init', array( $this, '_register_module' ) );

        add_action( 'save_post', array( $this, 'flush_module_cache' ) );
        add_action( 'deleted_post', array( $this, 'flush_module_cache' ) );
        add_action( 'switch_theme', array( $this, 'flush_module_cache' ) );
    }

    function display( $atts, $depth = 0 ){
        if ( $this->get_cached_module( $atts ) ) return;
        ob_start();

        $classes = 'modules-'.$this->id;
        $more_classes = $this->classes( $atts, $depth );
        $classes .= $more_classes ? ' ' . $more_classes : '';
        ?>
        <section class="section wpcom-modules <?php echo $classes;?>" id="modules-<?php echo $atts['modules-id'];?>" <?php echo $this->_style_inline($atts); echo $this->animate($atts); ?>>
            <?php $this->template($atts, $depth);?>
        </section>
        <?php echo $this->cache_module( $atts, ob_get_clean() );
    }

    function animate($atts){
        $animate = '';
        if(isset($atts['animate']) && $atts['animate']){
            $_animate = json_decode($atts['animate']);
            if(isset($_animate->animate) && $_animate->animate){
                $animate = ' data-aos="'.esc_attr($_animate->animate).'"';
                if(isset($_animate->easing) && $_animate->easing)
                    $animate .= ' data-aos-easing="'.esc_attr($_animate->easing).'"';
                if(isset($_animate->duration) && $_animate->duration)
                    $animate .= ' data-aos-duration="'.esc_attr($_animate->duration).'"';
            }
        }
        return $animate;
    }

    function style_inline_default( $atts ){
        $style = '';
        if(isset($atts['margin-top'])) $style .= 'margin-top: '.$atts['margin-top'].';';
        if(isset($atts['margin-bottom'])) $style .= 'margin-bottom: '.$atts['margin-bottom'].';';
        if(isset($atts['padding-top'])) $style .= 'padding-top: '.$atts['padding-top'].';';
        if(isset($atts['padding-bottom'])) $style .= 'padding-bottom: '.$atts['padding-bottom'].';';
        return $style;
    }

    function _style_inline( $atts ){
        $style = '';
        $default = $this->style_inline_default( $atts );
        $more = $this->style_inline( $atts );
        if($default || $more) $style = 'style="'.$default.''.$more.'"';
        return $style;
    }

    function classes( $atts, $depth ){
        $classes = $depth==0 ? 'container' : '';
        return $classes;
    }

    function module_options( $modules ){
        $modules->{$this->id} = array(
            'name'  => $this->name,
            'icon'  => $this->icon,
            'options' => $this->options
        );
        return $modules;
    }

    function _register_module(){
        add_action('wpcom_modules_'.$this->id, array( $this, 'display' ), 10, 2);
        add_filter('wpcom_modules', array( $this, 'module_options' ));
    }

    function template($atts, $depth){}
    function style($atts){}
    function style_inline($atts){}

    public function get_cached_module( $args ) {
        if( !$this->is_cache || is_customize_preview() ) return false;
        $this->object_id = get_queried_object_id();
        if(!$this->object_id) return false;

        $cache = wp_cache_get( $this->get_module_id_for_cache( $this->object_id ), 'module' );

        if ( ! is_array( $cache ) ) {
            $cache = array();
        }

        if ( isset( $cache[ $args['modules-id'] ] ) ) {
            echo $cache[ $args['modules-id'] ];
            return true;
        }

        return false;
    }

    public function cache_module( $args, $content ) {
        if( !$this->is_cache || is_customize_preview() ) return $content;
        $cache = wp_cache_get( $this->get_module_id_for_cache( $this->object_id ), 'module' );
        if ( ! is_array( $cache ) ) {
            $cache = array();
        }
        $cache[ $args['modules-id'] ] = $content;
        wp_cache_set( $this->get_module_id_for_cache( $this->object_id ), $cache, 'module' );
        return $content;
    }

    public function flush_module_cache() {
        global $is_flush_module_cache;
        if($is_flush_module_cache) return false;

        $pages = get_pages(array(
            'meta_key' => '_wp_page_template',
            'meta_value' => 'page-home.php'
        ));

        if($pages){
            foreach ($pages as $page) {
                wp_cache_delete( $this->get_module_id_for_cache( $page->ID, 'http' ), 'module' );
                wp_cache_delete( $this->get_module_id_for_cache( $page->ID, 'https' ), 'module' );
            }
        }
        $is_flush_module_cache = 1;
    }

    protected function get_module_id_for_cache( $module_id, $scheme = '' ) {
        if ( $scheme ) {
            $module_id_for_cache = $module_id . '-' . $scheme;
        } else {
            $module_id_for_cache = $module_id . '-' . ( is_ssl() ? 'https' : 'http' );
        }

        return apply_filters( 'wpcom_cached_module_id', $module_id_for_cache );
    }

    private function set_module_tempid($module, $mid){
        $module['id'] = $mid . '_' . $module['id'];
        if ($module['settings'] && isset($module['settings']['modules']) && $module['settings']['modules']) {
            foreach ($module['settings']['modules'] as $a => $s) {
                $module['settings']['modules'][$a] = $this->set_module_tempid($s, $mid);
            }
        }
        if ($module['settings'] && isset($module['settings']['girds']) && $module['settings']['girds']) {
            foreach ($module['settings']['girds'] as $b => $girds) {
                foreach ($girds as $c => $gird) {
                    $module['settings']['girds'][$b][$c] = $this->set_module_tempid($gird, $mid);
                }
            }
        }
        return $module;
    }
}

if( !function_exists('register_module') ){
    function register_module( $module_class ){
        global $wpcom_modules;
        if(!isset($wpcom_modules)) $wpcom_modules = array();
        $module = new $module_class();
        $wpcom_modules[$module->id] = $module;
    }
}

add_action('init', 'wpcom_my_module_list');
function wpcom_my_module_list(){
    $labels = array(
        'name' => '我的模块',
        'singular_name' => '我的模块',
        'edit_item' => '编辑模块',
        'search_items' => '搜索',
        'not_found' => '暂无模块',
        'not_found_in_trash' => '回收站为空'
    );
    $args = array(
        'labels' => $labels,
        'public' => current_user_can( 'customize' ) ? true : false,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'capability_type' => 'post',
        'hierarchical' => false,
        'show_in_rest' => false,
        'supports' => array('title', 'excerpt')
    );
    register_post_type('page_module', $args);
}

add_action( 'init', 'wpcom_page_module_rewrite' );
function wpcom_page_module_rewrite() {
    global $wp_rewrite, $permalink_structure;
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
    if($permalink_structure){
        $queryarg = 'post_type=page_module&p=';
        $wp_rewrite->add_rewrite_tag( '%mid%', '([^/]+)', $queryarg );
        $wp_rewrite->add_permastruct( 'page_module', 'page_module/%mid%', false );
    }
}

add_filter('post_type_link', 'wpcom_page_module_permalink', 5, 2);
function wpcom_page_module_permalink( $post_link, $id ) {
    global $wp_rewrite, $permalink_structure;
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
    if($permalink_structure) {
        $post = get_post($id);
        if (!is_wp_error($post) && $post->post_type == 'page_module') {
            $newlink = $wp_rewrite->get_extra_permastruct('page_module');
            $newlink = str_replace('%mid%', $post->ID, $newlink);
            $newlink = home_url(untrailingslashit($newlink));
            return $newlink;
        }
    }
    return $post_link;
}

add_filter('template_include', 'wpcom_template_include');
function wpcom_template_include($template){
    if(is_singular('page_module')) $template = get_query_template('page-home');
    return $template;
}

add_action( 'admin_menu', 'wpcom_my_module_menu' );
function wpcom_my_module_menu(){
    global $wpcom_panel;
    $enable = apply_filters('wpcom_show_my_module', true);
    $config = $wpcom_panel->get_demo_config();
    if($enable && !empty($config)) {
        add_submenu_page('wpcom-panel', '我的模块', '我的模块', 'edit_theme_options', 'edit.php?post_type=page_module', null);
    }
}

add_filter( 'manage_page_module_posts_columns', 'wpcom_my_module_posts_columns' );
add_action( 'manage_page_module_posts_custom_column' , 'wpcom_my_module_posts_custom_column', 10, 2 );
function wpcom_my_module_posts_columns($columns){
    $columns = array(
        'cb' => '<input type="checkbox" />',
        'title' => '模块标题',
        'desc' => '模块简介',
        'date' => '添加时间'
    );
    return $columns;
}
function wpcom_my_module_posts_custom_column( $column, $post_id ){
    if($column == 'desc'){
        echo get_the_excerpt($post_id);
    }
}

add_filter( 'post_row_actions', 'wpcom_my_module_row_actions', 10, 2 );
function wpcom_my_module_row_actions($actions, $post){
    if($post->post_type == 'page_module') {
        $edit = preg_replace('/>([^<]+)<\/a>/i', '/>编辑模块</a>', $actions['edit']);
        $editor_url = add_query_arg(array('visual-editor' => 'true'), get_edit_post_link($post->ID));

        $actions = array(
            'edit' => $edit,
            'customize' => '<a href="' . $editor_url . '" target="_blank">可视化编辑</a>',
            'trash' => $actions['trash'],
        );
    }
    return $actions;
}

add_filter( 'page_row_actions', 'wpcom_page_row_actions', 10, 2 );
function wpcom_page_row_actions($actions, $post){
    if($post->post_type == 'page' && get_page_template_slug($post->ID) == 'page-home.php') {
        $editor_url = add_query_arg(array('visual-editor' => 'true'), get_edit_post_link($post->ID));
        $_actions = array();
        foreach ($actions as $k => $v){
            $_actions[$k] = $v;
            if($k=='edit'){
                $_actions['customize'] = '<a style="font-weight: bold;" href="' . $editor_url . '" target="_blank">可视化编辑</a>';
            }
        }
        $actions = $_actions;
    }
    return $actions;
}

add_filter( 'parent_file', 'wpcom_my_module_parent_file' );
function wpcom_my_module_parent_file( $parent_file='' ){
    global $pagenow, $post;
    if ( !empty($_GET['action']) && ($_GET['action'] == 'edit') && $pagenow == 'post.php' && $post->post_type == 'page_module' ) {
        $parent_file = 'wpcom-panel';
    }
    return $parent_file;
}