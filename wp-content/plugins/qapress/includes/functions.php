<?php

add_action( 'wp_enqueue_scripts', 'QAPress_scripts', 20 );
function QAPress_scripts() {
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    wp_enqueue_style( 'QAPress', QAPress_URI . 'css/style.css', array(), QAPress_VERSION );

    $color = isset($qa_options['color']) && $qa_options['color'] ? $qa_options['color'] : '#1471CA';
    $hover = isset($qa_options['color_hover']) && $qa_options['color_hover'] ? $qa_options['color_hover'] : '#0D62B3';
    $custom_css = "
        .q-content .topic-tab,.q-content .q-answer .as-user,.q-content .q-answer .as-comment-name,.profile-QAPress-tab .QAPress-tab-item{color: {$color};}
        .q-content .q-topic-wrap a:hover,.q-content .q-answer .as-action a:hover,.q-content .topic-tab:hover,.q-content .topic-title:hover{color:{$hover};}
        .q-content .put-top,.q-content .topic-tab.current-tab,.q-content .q-answer .as-submit .btn-submit,.q-content .q-answer .as-comments-submit,.q-content .q-add-header .btn-post,.q-content .q-pagination .current,.q-btn-new,.profile-QAPress-tab .QAPress-tab-item.active,.q-mobile-ask a{background-color:{$color};}
        .q-content .q-answer .as-submit .btn-submit:hover,.q-content .q-answer .as-comments-submit:hover,.q-content .q-add-header .btn-post:hover,.q-content .topic-tab.current-tab:hover,.q-content .q-pagination a:hover,.q-btn-new:hover,.profile-QAPress-tab .QAPress-tab-item:hover,.q-mobile-ask a:hover{background-color:{$hover};}
        .q-content .q-answer .as-comments-input:focus,.profile-QAPress-tab .QAPress-tab-item{border-color: {$color};}
        .profile-QAPress-tab .QAPress-tab-item:hover{border-color: {$hover};}
        ";
    wp_add_inline_style( 'QAPress', $custom_css );
    // 载入js文件
    wp_enqueue_script( 'QAPress-js', QAPress_URI . 'js/scripts.min.js', array( 'jquery' ), QAPress_VERSION, true );

    wp_localize_script( 'QAPress-js', 'QAPress_js', array(
        'ajaxurl' => admin_url( 'admin-ajax.php'),
        'ajaxloading' => QAPress_URI . 'images/loading.gif'
    ) );
}

add_action( 'init', 'QAPress_register_category' );
function QAPress_register_category() {
    global $QAPress, $qa_slug, $qa_options, $pagenow, $wp_version;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    load_plugin_textdomain( 'wpcom', false, basename( QAPress_DIR ) . '/lang' ); 

    if(!isset($qa_slug) || !$qa_slug ){
        $qa_page_id = $qa_options['list_page'];
        $qa_page = get_post($qa_page_id);
        $qa_slug = isset($qa_page->ID) ? $qa_page->post_name : '';
    }
    $labels = array(
        'name' => '问题',
        'singular_name' => '问题',
        'add_new' => '添加',
        'add_new_item' => '添加',
        'edit_item' => '编辑',
        'new_item' => '添加',
        'view_item' => '查看',
        'search_items' => '查找',
        'not_found' => '没有内容',
        'not_found_in_trash' => '回收站为空',
        'parent_item_colon' => ''
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'capability_type' => 'qa_post',
        'hierarchical' => false,
        'rewrite' => array('slug' => $qa_slug, 'with_front' => 0),
        'show_in_rest' => true,
        'show_in_menu' => $QAPress->is_active() && current_user_can('manage_options') ? 'QAPress' : '',
        'supports' => array('title', 'editor', 'author', 'comments')
    );
    register_post_type('qa_post', $args);

    $is_hierarchical = $pagenow === 'edit.php' || ($pagenow === 'admin-ajax.php' && isset($_POST['action']) && $_POST['action'] === 'inline-save');
    register_taxonomy( 'qa_cat', null,
        array(
            'labels' => array(
                'add_new_item' => '添加分类',
                'edit_item' => '编辑分类',
                'update_item' => '更新分类'
            ),
            'public' => false,
            'show_ui' => true,
            'label' => '问答分类',
            'show_in_rest' => true,
            'rewrite' => array(
                'slug' => $qa_slug
            ),
            'meta_box_cb' => 'post_categories_meta_box',
            'hierarchical' => $is_hierarchical || version_compare($wp_version, '5.1', '<') ? true : false
        )
    );

    register_taxonomy_for_object_type( 'qa_cat', 'qa_post' );
}

add_action('_admin_menu', 'QAPress_capabilities');
function QAPress_capabilities() {
    global $wp_roles;
    if ( isset($wp_roles) ) {
        $wp_roles->add_cap( 'administrator', 'edit_qa_post' );
        $wp_roles->add_cap( 'administrator', 'read_qa_post' );
        $wp_roles->add_cap( 'administrator', 'delete_qa_post' );
        $wp_roles->add_cap( 'administrator', 'publish_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_others_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_private_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_published_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_published_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_others_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'read_private_qa_posts' );

        $wp_roles->add_cap( 'editor', 'edit_qa_post' );
        $wp_roles->add_cap( 'editor', 'read_qa_post' );
        $wp_roles->add_cap( 'editor', 'delete_qa_post' );
        $wp_roles->add_cap( 'editor', 'publish_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_others_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_private_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_published_qa_posts' );
        $wp_roles->add_cap( 'editor', 'delete_qa_posts' );
        $wp_roles->add_cap( 'editor', 'delete_published_qa_posts' );
        $wp_roles->add_cap( 'editor', 'delete_others_qa_posts' );
        $wp_roles->add_cap( 'editor', 'read_private_qa_posts' );
 
        $wp_roles->add_cap( 'author', 'edit_qa_post' );
        $wp_roles->add_cap( 'author', 'read_qa_post' );
        $wp_roles->add_cap( 'author', 'delete_qa_post' );
        $wp_roles->add_cap( 'author', 'publish_qa_posts' );
        $wp_roles->add_cap( 'author', 'edit_qa_posts' );
         
        $wp_roles->add_cap( 'contributor', 'edit_qa_post' );
        $wp_roles->add_cap( 'contributor', 'read_qa_post' );
        $wp_roles->add_cap( 'contributor', 'publish_qa_posts' );
        $wp_roles->add_cap( 'contributor', 'edit_qa_posts' );

        $wp_roles->add_cap( 'subscriber', 'edit_qa_post' );
        $wp_roles->add_cap( 'subscriber', 'read_qa_post' );
        $wp_roles->add_cap( 'subscriber', 'publish_qa_posts' );
        $wp_roles->add_cap( 'subscriber', 'edit_qa_posts' );
    }
}

add_filter( 'rest_prepare_taxonomy', 'QAPress_prepare_taxonomy', 10, 3 );
function QAPress_prepare_taxonomy( $response, $taxonomy, $request ){
    $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
    if( $context === 'edit' && $taxonomy->name == 'qa_cat' && $taxonomy->hierarchical === false ){
        $data_response = $response->get_data();
        $data_response['hierarchical'] = true;
        $response->set_data( $data_response );
    }
    return $response;
}

add_action( 'admin_menu', 'QAPress_cat_menu');
function QAPress_cat_menu(){
    global $QAPress;
    if($QAPress->is_active()){
        add_submenu_page('QAPress', '问题分类', '问题分类', 'edit_theme_options', 'edit-tags.php?taxonomy=qa_cat', null);
        add_submenu_page('QAPress', '问答设置', '问答设置', 'edit_theme_options', 'admin.php?page=QAPress', null);
    }
}

add_filter('manage_edit-qa_cat_columns', 'QAPress_remove_column' );
function QAPress_remove_column( $columns ){
    unset($columns['posts']);
    return $columns;
}

add_filter('body_class', 'QAPress_body_class' );
function QAPress_body_class( $classes ){
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(isset($qa_options['list_page']) && $qa_options['list_page'] && is_page($qa_options['list_page'])){
        $classes[] = 'qapress qapress-list';
    }else if(isset($qa_options['new_page']) && $qa_options['new_page'] && is_page($qa_options['new_page'])){
        $classes[] = 'qapress qapress-new';
    }
    return $classes;
}

add_action('admin_head', 'QAPress_remove_cat_fileds');
function QAPress_remove_cat_fileds(){
    remove_all_actions( 'qa_cat_add_form_fields' );
    remove_all_actions( 'qa_cat_edit_form_fields' );
    remove_all_actions( 'created_qa_cat' );
    remove_all_actions( 'edited_qa_cat' );
}

add_filter( 'parent_file', 'QAPress_parent_file' );
function QAPress_parent_file( $parent_file='' ){
    global $pagenow;
    if ( !empty($_GET['taxonomy']) && ($_GET['taxonomy'] == 'qa_cat') && ($pagenow == 'edit-tags.php'||$pagenow == 'term.php') ) {
        $parent_file = 'QAPress';
    }
    return $parent_file;
}

add_filter( 'submenu_file', 'QAPress_submenu_file' );
function QAPress_submenu_file( $submenu_file='' ){
    global $pagenow;
    $screen = get_current_screen();
    if ( $pagenow == 'admin.php' && $screen->base == 'toplevel_page_QAPress' ) {
        $submenu_file = 'admin.php?page=QAPress';
    }
    return $submenu_file;
}

add_filter( 'wpcom_init_plugin_options', 'QAPress_cats' );
function QAPress_cats($res){
    require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
    if(isset($res['plugin-slug']) && $res['plugin-slug'] == $GLOBALS['QAPress']->plugin_slug){
        $res['qa_cat'] = WPCOM_ADMIN_UTILS::category('qa_cat');
    }
    return $res;
}

function QAPress_format_date($time){
    $t = current_time('timestamp') - $time;
    $f=array(
        '31536000'=>'年',
        '2592000'=>'个月',
        '604800'=>'星期',
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    if($t<=0){
        return '1秒前';
    }
    foreach ($f as $k=>$v){
        if (0 !=$c=floor($t/(int)$k)) {
            return $c.$v.'前';
        }
    }
}

function QAPress_category( $post ){
    $cats = get_the_terms($post->ID, 'qa_cat');

    if($cats){
        return $cats[0]->name;
    }
}

function QAPress_categorys(){
    // WP 4.5+
    $terms = get_terms( array(
            'taxonomy' => 'qa_cat',
            'hide_empty' => false
        )
    );

    return $terms;
}


add_filter( 'wp_title_parts', 'QAPress_title_parts', 5 );
function QAPress_title_parts( $parts ){
    global $qa_options, $current_cat;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(is_page($qa_options['list_page'])){
        global $wp_query, $post, $wpcomqadb;
        if( is_singular('qa_post') ){
            $parts[] = $post->post_title;
        }else if(isset($wp_query->query['qa_cat']) && $wp_query->query['qa_cat']){
            if(!$current_cat) $current_cat = get_term_by('slug', $wp_query->query['qa_cat'], 'qa_cat');
            $parts[] = $current_cat ? $current_cat->name : '';
        }

        if(isset($wp_query->query['qa_page']) && $wp_query->query['qa_page']){
            array_unshift($parts, '第'.$wp_query->query['qa_page'].'页');
        }
    }
    return $parts;
}

function QAPress_editor_settings($args = array()){
    $allow_img = isset($args['allow_img']) && $args['allow_img'] ? 1 : 0;
    return array(
        'textarea_name' => $args['textarea_name'],
        'media_buttons' => false,
        'quicktags' => false,
        'tinymce' => array(
            'statusbar' => false,
            'height'        => isset($args['height']) ? $args['height'] : 120,
            'toolbar1' => 'bold,italic,underline,blockquote,bullist,numlist'.($allow_img?',QAImg':''),
            'toolbar2' => '',
            'toolbar3' => ''
        )
    );
}

add_filter( 'mce_external_plugins', 'QAPress_mce_plugin');
function QAPress_mce_plugin($plugin_array){
    $plugin_array['QAImg'] = QAPress_URI . 'js/QAImg.min.js';
    return $plugin_array;
}

function QAPress_mail( $to, $subject, $content ){
    $html = '<p>亲爱的用户，您好！</p>';
    $html .= $content;
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $html, $headers);
}

add_filter( 'wpcom_profile_tabs', 'QAPress_add_profile_tabs' );
function QAPress_add_profile_tabs( $tabs ){
    $tabs += array(
        25 => array(
            'slug' => 'questions',
            'title' => '问答'
        )
    );
    return $tabs;
}

add_action( 'pre_get_comments', 'QAPress_pre_get_comments', 10 );
function QAPress_pre_get_comments( $q ) {
    if( !(is_admin() && ! wp_doing_ajax()) && !$q->query_vars['type'] && !$q->query_vars['parent'] ){
        $q->query_vars['type__not_in'] = array('answer', 'qa_comment');
    }
    return $q;
}
add_action('wpcom_profile_tabs_questions', 'QAPress_questions');
function QAPress_questions() {
    global $profile, $wpcomqadb, $current_user;
    $all_cats = QAPress_categorys();
    $questions = $wpcomqadb->get_questions_by_user($profile->ID, 20, 1);
    $q_total = $wpcomqadb->get_questions_total_by_user($profile->ID);
    $q_numpages = ceil($q_total/20);

    $answers = $wpcomqadb->get_answers_by_user($profile->ID, 10, 1);
    $a_total = $wpcomqadb->get_answers_total_by_user($profile->ID);
    $a_numpages = ceil($a_total/10);

    $is_user = isset($current_user) && isset($current_user->ID) && $current_user->ID == $profile->ID;

    if($questions){
        $users_id = array();
        foreach($questions as $p){
            if(!in_array($p->user, $users_id)) $users_id[] = $p->user;
            if(!in_array($p->last_answer, $users_id)) $users_id[] = $p->last_answer;
        }

        $user_array = get_users(array('include'=>$users_id));
        $users = array();
        foreach($user_array as $u){
            $users[$u->ID] = $u;
        }
    }
    ?>
    <div class="profile-QAPress-tab" data-user="<?php echo $profile->ID;?>">
        <div class="QAPress-tab-item active">问题</div>
        <div class="QAPress-tab-item">回答</div>
    </div>
    <div class="profile-QAPress-content q-content active">
        <?php
        if($questions){
            global $post;
            foreach ($questions as $post) { ?>
                <div class="q-topic-item">
                    <div class="reply-count">
                        <span class="count-of-replies" title="回复数"><?php echo $post->comment_count;?></span>
                        <span class="count-seperator">/</span>
                        <span class="count-of-visits" title="点击数"><?php echo ($post->views?$post->views:0);?></span>
                    </div>
                    <div class="topic-title-wrapper"><span class="topiclist-tab"><?php echo QAPress_category($post);?></span><a class="topic-title" href="<?php echo get_permalink($post->ID);?>" title="<?php echo esc_attr(get_the_title($post->ID));?>" target="_blank"><?php the_title()?></a>
                    </div>
                    <div class="last-time">
                        <?php if($post->post_mime_type){ ?><a class="last-time-user" href="<?php echo get_permalink($post->ID);?>#answer" target="_blank"><?php echo get_avatar( $post->post_mime_type, '60' );?></a> <?php } ?>
                        <span class="last-active-time"><?php echo QAPress_format_date(get_post_modified_time());?></span>
                    </div>
                </div>
            <?php } if($q_numpages>1) { ?>
                <div class="load-more-wrap"><a href="javascript:;" class="load-more j-user-questions">点击查看更多</a></div>
            <?php }
        }else{ ?>
            <div class="profile-no-content"><?php echo ($is_user?'你':'该用户');?>还没有发布过问题。</div>
        <?php } ?>
    </div>
    <div class="profile-QAPress-content profile-comments-list">
    <?php if($answers){ global $post;?>
        <?php foreach($answers as $answer){ $post = $wpcomqadb->get_question($answer->comment_post_ID);?>
            <div class="comment-item">
                <div class="comment-item-link">
                    <a target="_blank" href="<?php echo esc_url(get_permalink($post->ID));?>#answer">
                        <i class="fa fa-comments"></i> <?php $excerpt = wp_trim_words( $answer->comment_content, 100, '...' ); echo $excerpt ? $excerpt : '（过滤内容）' ?>
                    </a>
                </div>
                <div class="comment-item-meta">
                    <span><?php echo QAPress_format_date(strtotime($answer->comment_date));?> 回答 <a target="_blank" href="<?php echo get_permalink($post->ID);?>"><?php echo get_the_title($post->ID);?></a></span>
                </div>
            </div>
        <?php } if($a_numpages>1) { ?>
            <div class="load-more-wrap"><a href="javascript:;" class="load-more j-user-answers">点击查看更多</a></div>
        <?php } }else{ ?>
            <div class="profile-no-content"><?php echo ($is_user?'你':'该用户');?>还没有回答过问题。</div>
        <?php } ?>
    </div>
<?php }

add_action( 'wp_ajax_QAPress_user_questions', 'QAPress_user_questions' );
add_action( 'wp_ajax_nopriv_QAPress_user_questions', 'QAPress_user_questions' );
function QAPress_user_questions(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
        global $wpcomqadb;
        $page = $_POST['page'];
        $page = $page ? $page : 1;
        $all_cats = QAPress_categorys();
        $questions = $wpcomqadb->get_questions_by_user($user->ID, 20, $page);
        if($questions){
            global $post;
            foreach($questions as $post){ ?>
                <div class="q-topic-item">
                    <div class="reply-count">
                        <span class="count-of-replies" title="回复数"><?php echo $post->comment_count;?></span>
                        <span class="count-seperator">/</span>
                        <span class="count-of-visits" title="点击数"><?php echo ($post->views?$post->views:0);?></span>
                    </div>
                    <div class="topic-title-wrapper"><span class="topiclist-tab"><?php echo QAPress_category($post);?></span><a class="topic-title" href="<?php echo get_permalink($post->ID);?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank"><?php the_title();?></a>
                    </div>
                    <div class="last-time">
                        <?php if($post->post_mime_type){ ?><a class="last-time-user" href="<?php echo get_permalink($post->ID);?>#answer" target="_blank"><?php echo get_avatar( $post->post_mime_type, '60' );?></a> <?php } ?>
                        <span class="last-active-time"><?php echo QAPress_format_date(get_post_modified_time());?></span>
                    </div>
                </div>
            <?php }
        }else{ echo 0; }
    }
    exit;
}

add_action( 'wp_ajax_QAPress_user_answers', 'QAPress_user_answers' );
add_action( 'wp_ajax_nopriv_QAPress_user_answers', 'QAPress_user_answers' );
function QAPress_user_answers(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
        global $wpcomqadb;
        $page = $_POST['page'];
        $page = $page ? $page : 1;
        $answers = $wpcomqadb->get_answers_by_user($user->ID, 10, $page);

        if($answers){
            global $post;
            foreach($answers as $answer){ $post = $wpcomqadb->get_question($answer->comment_post_ID);?>
                <div class="comment-item">
                    <div class="comment-item-link">
                        <a target="_blank" href="<?php echo esc_url(get_permalink($post->ID));?>#answer">
                            <i class="fa fa-comments"></i> <?php $excerpt = wp_trim_words( $answer->comment_content, 100, '...' ); echo $excerpt ? $excerpt : '（过滤内容）' ?>
                        </a>
                    </div>
                    <div class="comment-item-meta">
                        <span><?php echo QAPress_format_date(strtotime($answer->comment_date));?> 回答 <a target="_blank" href="<?php echo get_permalink($post->ID);?>"><?php echo get_the_title($post->ID);?></a></span>
                    </div>
                </div>
            <?php }
        }else{ echo 0; }
    }
    exit;
}

add_action('wp_loaded', 'wpcom_tinymce_replace_start');
if ( ! function_exists( 'wpcom_tinymce_replace_start' ) ) {
    function wpcom_tinymce_replace_start() {
        if(!is_admin()) {
            global $is_IE;
            if (!$is_IE) return false;
            ob_start("wpcom_tinymce_replace_url");
        }
    }
}

add_action('shutdown', 'wpcom_tinymce_replace_end');
if ( ! function_exists( 'wpcom_tinymce_replace_end' ) ) {
    function wpcom_tinymce_replace_end() {
        if(!is_admin()) {
            global $is_IE;
            if (!$is_IE) return false;
            if (ob_get_level() > 0) ob_end_flush();
        }
    }
}

if ( ! function_exists( 'wpcom_tinymce_replace_url' ) ) {
    function wpcom_tinymce_replace_url( $str ){
        $regexp = "/\/wp-includes\/js\/tinymce/i";
        $path = str_replace(get_option( 'siteurl' ), '', QAPress_URI);
        $str = preg_replace( $regexp, $path . 'js/tinymce', $str );
        $str = preg_replace( '/tinymce\.Env\.ie \< 11/i', 'tinymce.Env.ie < 8', $str );
        $str = preg_replace( '/wp-editor-wrap html-active/i', 'wp-editor-wrap tmce-active', $str );
        return $str;
    }
}

add_filter( 'user_can_richedit', 'wpcom_can_richedit' );
if ( ! function_exists( 'wpcom_can_richedit' ) ) {
    function wpcom_can_richedit( $wp_rich_edit ){
        global $is_IE;
        if( !$wp_rich_edit && $is_IE && !is_admin() ){
            $wp_rich_edit = 1;
        }
        return $wp_rich_edit;
    }
}

add_filter( 'pre_wp_update_comment_count_now', 'QAPress_update_comment_count', 10, 3 );
function QAPress_update_comment_count( $count, $old, $post_id ){
    global $wpdb;
    if ( !$post = get_post($post_id) ) return $count;
    if($post->post_type=='qa_post'){
        $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_parent = '0'", $post_id ) );
    }
    return $count;
}

// 用于关闭主题默认的评论框
add_filter( 'comments_open', 'QAPress_single_comments_open' );
function QAPress_single_comments_open( $open ) {
    global $qa_options, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if( is_page($qa_options['list_page']) || (isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post') ) {
        $open = false;
    }
    return $open;
}

// 用于head结束后将wp_query设置为问答页面，主要用于面包屑导航、边栏等的获取与问答列表页面一致
add_action( 'wp_head', 'QAPress_single_use_page_tpl', 99999 );
function QAPress_single_use_page_tpl(){
    global $qa_options, $post, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if( $wp_query->is_main_query() && is_singular('qa_post') ) {
        $post = get_post($qa_options['list_page']);
        $wp_query->is_page = 1;
        $wp_query->is_single = 0;
        $wp_query->query['qa_id'] = $wp_query->queried_object_id;
        $wp_query->queried_object_id = $qa_options['list_page'];
        $wp_query->queried_object = $post;
        $wp_query->posts[0] = $post;
    }
}

// 用于问题正文，重置$post为问题本身
add_action( 'loop_start', 'QAPress_loop_start' );
function QAPress_loop_start(){
    global $qa_options, $post, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if( $wp_query->is_main_query() && isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' ) {
        $qa_page_id = $qa_options['list_page'];
        $post = get_post($qa_page_id);
    }
}

// 用于重置工具条编辑链接
add_action( 'wp_footer', 'QAPress_wp_footer', 1 );
function QAPress_wp_footer(){
    global $wp_query, $post;
    if ( isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' && isset($wp_query->query['qa_id']) ) {
        remove_filter('the_content', 'QAPress_single_content', 1);
        $post = get_post($wp_query->query['qa_id']);
        $wp_query->is_page = 0;
        $wp_query->is_single = 1;
        $wp_query->queried_object = $post;
        $wp_query->queried_object_id = $wp_query->query['qa_id'];
        $wp_query->posts[0] = $post;
    }
}

// 后台按时间排序
add_action('pre_get_posts', 'QAPress_admin_order');
function QAPress_admin_order( $q ) {
    if(is_admin() && function_exists('get_current_screen')){
        $s = get_current_screen();
        if ( isset($s->base) && $s->base === 'edit' && isset($s->post_type) && $s->post_type === 'qa_post' && $q->is_main_query() ) {
            if( !isset($_GET[ 'orderby' ]) ) {
                $q->set('orderby', 'date');
                $q->set('order', 'desc');
            }
        }
    }
}

add_filter('the_comments', 'QAPress_admin_comments' );
function QAPress_admin_comments($comments){
    global $pagenow;
    if( is_admin() && $pagenow=='index.php' ){
        if($comments){
            foreach ($comments as $k => $comment) {
                if( $comment->comment_type=='answer' || $comment->comment_type=='qa_comment' ){
                    $comments[$k]->comment_type = '';
                }
            }
        }
    }
    return $comments;
}

// 2.0 数据迁移
add_action( 'admin_menu', 'QAPress_post_2_0' );
function QAPress_post_2_0(){
    global $wpdb, $QAPress;
    $table_q = $wpdb->prefix.'wpcom_questions';
    $table_a = $wpdb->prefix.'wpcom_answers';
    $table_c = $wpdb->prefix.'wpcom_comments';

    if( $wpdb->get_var("SHOW TABLES LIKE '$table_q'") != $table_q ) return false;

    $sql = "SELECT * FROM `$table_q` WHERE `flag` > -1 OR `flag` is null";
    $questions = $wpdb->get_results($sql);

    if($questions){
        foreach ($questions as $question) {
            $post = array(
                'post_author' => $question->user,
                'post_date' => $question->date,
                'post_modified' => $question->modified,
                'post_content' => $question->content,
                'post_title' => $question->title,
                'menu_order' => $question->flag ? $question->flag : 0,
                'comment_count' => $question->answers,
                'post_mime_type' => $question->last_answer,
                'post_status' => 'publish',
                'post_type' => 'qa_post',
                'comment_status' => 'open',
            );
            // 插入文章
            $pid = wp_insert_post($post);
            // 插入文章信息
            if($pid){
                update_post_meta($pid, 'views', $question->views);
                wp_set_object_terms( $pid, array( (int)$question->category ), 'qa_cat' );

                // 插入回答信息
                $answers = $wpdb->get_results("SELECT * FROM `$table_a` WHERE `question` = '$question->ID'");
                if($answers){
                    foreach ($answers as $answer) {
                        $user = get_user_by('ID', $answer->user);
                        $data = array(
                            'comment_post_ID' => $pid,
                            'comment_content' => $answer->content,
                            'comment_type' => 'answer',
                            'comment_parent' => 0,
                            'user_id' => $answer->user,
                            'comment_author_email' => $user->user_email,
                            'comment_author' => $user->display_name,
                            'comment_date' => $answer->date,
                            'comment_approved' => 1,
                            'comment_karma' => $answer->comments
                        );

                        $answer_id = wp_insert_comment($data);

                        // 插入评论信息
                        if($answer_id){
                            $comments = $wpdb->get_results("SELECT * FROM `$table_c` WHERE `answer` = '$answer->ID'");
                            if($comments){
                                foreach ($comments as $comment) {
                                    $cuser = get_user_by('ID', $comment->user);
                                    $data = array(
                                        'comment_post_ID' => $pid,
                                        'comment_content' => $comment->content,
                                        'comment_type' => 'qa_comment',
                                        'comment_parent' => $answer_id,
                                        'user_id' => $comment->user,
                                        'comment_author_email' => $cuser->user_email,
                                        'comment_author' => $cuser->display_name,
                                        'comment_date' => $comment->date,
                                        'comment_approved' => 1
                                    );

                                    wp_insert_comment($data);
                                }
                            }
                        }
                    }
                }
                $wpdb->update($table_q, array('flag' => -($pid)), array('ID' => $question->ID));
            }
        }
    }
}

// 2.3 评论字段修改
add_action( 'admin_menu', 'QAPress_comment_2_3' );
function QAPress_comment_2_3(){
    global $wpdb;
    if(get_option('_QAPress_2_3')) return false;

    $table_c = $wpdb->prefix.'comments';
    $sql = "SELECT * FROM `$table_c` WHERE `comment_type`='comment' AND `comment_parent`>0 AND `comment_approved`=1";
    $comments = $wpdb->get_results($sql);
    if($comments){
        foreach ($comments as $comment) {
            if($comment->comment_post_ID && $post = get_post($comment->comment_post_ID)){
                if($post->post_type=='qa_post'){
                    $wpdb->update($table_c, array('comment_type' => 'qa_comment'), array('comment_ID' => $comment->comment_ID));
                }
            }
        }
        update_option('_QAPress_2_3', '1');
    }
}