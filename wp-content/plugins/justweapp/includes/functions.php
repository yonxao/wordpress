<?php

add_action( 'admin_menu', 'WWA_enqueue_admin_style');
function WWA_enqueue_admin_style (){
    wp_enqueue_style( "wwa-admin", WWA_URI . "css/admin.css", false, WWA_VERSION, "all");
}

add_action( 'admin_menu', 'WWA_enqueue_admin_js');
function WWA_enqueue_admin_js (){
    global $pagenow;
    if ( $pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == $GLOBALS['WWA']->plugin_slug ) {
        wp_enqueue_script("wwa-admin", WWA_URI . "js/admin.js", array('jquery'), WWA_VERSION, true);
    }
}

add_action( 'wp_ajax_WWA_commit', 'WWA_commit' );
function WWA_commit(){
    global $WWA;
    $options = WWA_options();
    $api_url = 'http://www.wpcom.cn/weixin-open/commit/'.$WWA->info['plugin_id'];
    $tabbar = array();
    if($options['url']){
        foreach ($options['url'] as $i => $url) {
            $tabbar[] = array(
                'url' => $url,
                'title' => $options['title'][$i]
            );
        }
    }
    $body = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token')
    );
    $result = wp_remote_request($api_url, array('method' => 'POST', 'timeout' => 30, 'body' => $body));
    if(is_array($result)){
        echo $result['body'];
    }else{
        print_r($result);
    }
    exit;
}

add_action( 'wp_ajax_WWA_submit_audit', 'WWA_submit_audit' );
function WWA_submit_audit(){
    global $WWA;
    $api_url = 'http://www.wpcom.cn/weixin-open/submitaudit/'.$WWA->info['plugin_id'];
    $body = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token')
    );
    $result = wp_remote_request($api_url, array('method' => 'POST', 'timeout' => 30, 'body' => $body));
    if(is_array($result)){
        echo $result['body'];
    }else{
        print_r($result);
    }
    exit;
}


add_action( 'wp_ajax_WWA_release', 'WWA_release' );
function WWA_release(){
    global $WWA;
    $api_url = 'http://www.wpcom.cn/weixin-open/release/'.$WWA->info['plugin_id'];
    $body = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token')
    );
    $result = wp_remote_request($api_url, array('method' => 'POST', 'timeout' => 30, 'body' => $body));
    if(is_array($result)){
        echo $result['body'];
    }else{
        print_r($result);
    }
    exit;
}

add_action( 'plugins_loaded', 'WWA_wptexturize', 1 );
function WWA_wptexturize(){
    if(WWA_is_rest()) add_filter( 'run_wptexturize', '__return_false', 20 );
}

add_action('rest_api_init', 'WWA_rest_api' );
function WWA_rest_api(){
    global $WWA;
    if($WWA->is_active()){
        register_rest_field( 'post',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_post_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'page',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_page_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'kuaixun',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_kuaixun_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'special',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_special_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'qa_post',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_qapost_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'comment',
            'reply_to',
            array(
                'get_callback'    => 'WWA_rest_comment_reply_to',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'qacomment',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_qacomments_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'category',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_cat_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }
}

function WWA_rest_post_metas($object, $field_name, $request){
    global $options; // 兼容主题
    $wwa_options = WWA_options();
    $post_id = isset($request['id']) && $request['id'] ? $request['id'] : 0;
    $metas = array();
    // 缩略图
    $img_url = WWA_thumbnail_url($object['id'], 'full');
    if($img_url) {
        $metas['cover'] = $img_url;
        $metas['thumb'] = WWA_thumbnail_url($object['id'], 'post-thumbnail');
    }

    $post = get_post($object['id']);
    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', apply_filters( 'the_content', $post->post_content ), $matches);
    $multimage = get_post_meta($object['id'], 'wpcom_multimage', true);
    $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;

    if(isset($matches[1]) && isset($matches[1][3]) && $multimage) {
        $metas['thumbs'] = array_slice($matches[1], 0, 3);
    }

    if( function_exists('the_views') ) {
        $views = get_post_meta($object['id'], 'views', true);
        $views = $views ? $views : 1;
        if($post_id) {
            update_post_meta($post_id, 'views', $views+1);
        }
        $metas['views'] = $views+1;
    }

    $metas['comments'] = get_comments_number($object['id']);

    if($object['categories']){
        $cats = array();
        foreach ($object['categories'] as $cat) {
            $cats[] = array(
                'id' => $cat,
                'name' => get_cat_name($cat)
            );
        }
        $metas['cats'] = $cats;
    }

    $video = get_post_meta($object['id'], 'wpcom_video', true);
    if($video){
        $metas['video'] = $video;
    }

    $metas['author_name'] = get_the_author_meta('display_name', $object['author']);

    $metas['is_like'] = 0;
    if($post_id) {
        $user = wp_get_current_user();
        // 用户关注的文章
        if($user->ID){
            $u_favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
            $u_favorites = $u_favorites ? $u_favorites : array();

            if(in_array($post_id, $u_favorites)){ // 用户是否关注本文
                $metas['is_like'] = 1;
            }
        }

        // 上下篇文章
        $pre = get_previous_post();
        $next = get_next_post();
        $metas['previous'] = $pre ? array(
            'id' => $pre->ID,
            'title' => get_the_title($pre->ID),
            'thumb' => WWA_thumbnail_url($pre->ID, 'post-thumbnail'),
            'date_gmt' => $pre->post_date_gmt
        ) : array();
        $metas['next'] = $next ? array(
            'id' => $next->ID,
            'title' => get_the_title($next->ID),
            'thumb' => WWA_thumbnail_url($next->ID, 'post-thumbnail'),
            'date_gmt' => $next->post_date_gmt
        ) : array();

        // 相关文章
        $related = WWA_get_related_posts($post_id, isset($wwa_options['related_num']) ? $wwa_options['related_num'] : 5);
        if( $related ) {
            $metas['related'] = array();
            global $post;
            foreach ( $related as $post ) { setup_postdata($post);
                $arr = array(
                    'id' => $post->ID,
                    'title' => get_the_title(),
                    'thumb' => WWA_thumbnail_url($post->ID, 'post-thumbnail'),
                    'date_gmt' => $post->post_date_gmt,
                    'comments' => $post->comment_count,
                    'excerpt' => get_the_excerpt()
                );

                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', apply_filters( 'the_content', $post->post_content ), $matches);
                $multimage = get_post_meta($post->ID, 'wpcom_multimage', true);
                $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;

                if(isset($matches[1]) && isset($matches[1][3]) && $multimage) {
                    $arr['thumbs'] = array_slice($matches[1], 0, 3);
                }

                $category = get_the_category();
                $cat = $category ? $category[0] : '';
                if($cat) {
                    $arr['cat'] = array(
                        'id' => $cat->cat_ID,
                        'name' => $cat->name
                    );
                }

                if(function_exists('the_views')) {
                    $views = get_post_meta($post->ID, 'views', true);
                    $views = $views ? $views : 1;
                    $arr['views'] = $views;
                }
                $metas['related'][] = $arr;
            }
        }

        // 点赞
        $likes = get_post_meta($object['id'], 'wpcom_likes', true);
        $metas['likes'] = 0;
        if($likes) $metas['likes'] = $likes;

        $metas['seo'] = WWA_seo('single', $object['id']);
    }

    return $metas;
}

function WWA_rest_qapost_metas($object, $field_name, $request){
    $wwa_options = WWA_options();
    $post_id = isset($request['id']) && $request['id'] ? $request['id'] : 0;
    $metas = array();
    $post = get_post($object['id']);
    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', apply_filters( 'the_content', $post->post_content ), $matches);

    if(isset($matches[1]) && isset($matches[1][0])) {
        $metas['thumbs'] = array_slice($matches[1], 0, 3);
    }

    if( function_exists('the_views') ) {
        $views = get_post_meta($object['id'], 'views', true);
        $views = $views ? $views : 1;
        if($post_id) {
            update_post_meta($post_id, 'views', $views+1);
        }
        $metas['views'] = $views+1;
    }

    $metas['comments'] = get_comments_number($object['id']);

    $metas['author_name'] = get_the_author_meta('display_name', $object['author']);
    $metas['author_avatar'] = get_avatar_url($object['author']);

    $cats = get_the_terms($post->ID, 'qa_cat');

    if($cats){
        $metas['cat'] = array('name'=>$cats[0]->name,'id' => $cats[0]->term_id);
    }

    $metas['sticky'] = $post->menu_order;

    if($post_id) {
        $metas['seo'] = WWA_seo('single', $object['id']);
    }
    return $metas;
}

function WWA_rest_qacomments_metas($object, $field_name, $request){
    $metas = array();
    $comment = get_comment($object['id']);
    $metas['comments'] = $comment ? $comment->comment_karma : 0;
    return $metas;
}

function WWA_rest_page_metas($object, $field_name, $request){
    $metas = array();
    $img = get_the_post_thumbnail_url($object['id'], 'full');
    if($img) $metas['cover'] = $img;
    $metas['seo'] = WWA_seo('single', $object['id']);
    return $metas;
}

function WWA_get_related_posts($post, $showposts=5){
    $options = WWA_options();

    $args = array(
        'post__not_in' => array($post),
        'showposts' => $showposts,
        'ignore_sticky_posts' => 1,
        'orderby' => 'rand'
    );

    if(isset($options['related_by']) && $options['related_by']=='1'){
        $tag_list = array();
        $tags = get_the_tags($post);
        if($tags) {
            foreach ($tags as $tag) {
                $tid = $tag->term_id;
                if (!in_array($tid, $tag_list)) {
                    $tag_list[] = $tid;
                }
            }
        }
        $args['tag__in'] = $tag_list;
    }else{
        $cat_list = array();
        $categories = get_the_category($post);
        if($categories) {
            foreach ($categories as $category) {
                $cid = $category->term_id;
                if (!in_array($cid, $cat_list)) {
                    $cat_list[] = $cid;
                }
            }
        }
        $args['category'] = join(',', $cat_list);
    }

    return get_posts($args);
}

function WWA_rest_kuaixun_metas( $object, $field_name, $request ){
    $metas = array();
    // 缩略图
    $img_url = WWA_thumbnail_url($object['id'], 'full');
    if($img_url) $metas['thumb'] = $img_url;
    return $metas;
}

function WWA_rest_special_metas( $object, $field_name, $request ){
    $metas = array();
    // 缩略图
    $img_url = get_term_meta( $object['id'], 'wpcom_thumb', true );
    $metas['thumb'] = $img_url;

    // 最新3篇文章
    $metas['posts'] = array();
    $args = array(
        'posts_per_page' => 3,
        'tax_query' => array(
            array(
                'taxonomy' => 'special',
                'field' => 'term_id',
                'terms' => $object['id']
            )
        )
    );
    $postslist = get_posts( $args );
    global $post;
    foreach($postslist as $post){ setup_postdata($post);
        $metas['posts'][] = array(
            'id' => $post->ID,
            'title' => get_the_title()
        );
    } wp_reset_postdata();
    $metas['seo'] = WWA_seo('term', $object['id']);
    return $metas;
}

add_filter( 'rest_post_query', 'WWA_rest_post_query', 10, 2 );
function WWA_rest_post_query( $args, $request ){
    if(isset($request['home']) && $request['home']=='true'){
        $options = WWA_options();
        $args['category__not_in'] = isset($options['cats_exclude'])&&$options['cats_exclude'] ? $options['cats_exclude'] : array();
    }
    return $args;
}

function WWA_is_rest(){
    $prefix = rest_get_url_prefix();
    $rest_url = wp_parse_url( site_url( $prefix ) );
    $current_url = wp_parse_url( add_query_arg( array( ) ) );
    $rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
    if(!$rest) $rest = isset($current_url['query']) && strpos( $current_url['query'], 'rest_route=', 0 ) === 0;
    return $rest;
}

add_action( 'pre_get_posts', 'WWA_pre_get_posts' );
function WWA_pre_get_posts( $query ) {
    if ( WWA_is_rest() ) {
        $tax_query = $query->get('tax_query');
        if($tax_query){
            $new_tax = array();
            foreach ($tax_query as $i => $tax) {
                if($tax['taxonomy']=='category'){
                    $tax['include_children'] = true;
                }
                $new_tax[$i] = $tax;
            }
            $query->set('tax_query', $new_tax);
        }

        if($query->get('post_type') == 'qa_post'){
            $orderby = 'menu_order ' . $query->get('orderby');
            $query->set('orderby', $orderby);
        }
    }
}

function WWA_rest_cat_metas($object, $field_name, $request){
    $metas = array();
    $metas['seo'] = WWA_seo('term', $object['id']);
    return $metas;
}

function WWA_rest_comment_reply_to($object, $field_name, $request){
    if($object['parent']){
        $parent = get_comment($object['parent']);
        return $parent->comment_author;
    }
}

add_filter( 'rest_comment_query', 'WWA_rest_comment_query', 10, 2 );
function WWA_rest_comment_query( $prepared_args, $request ){
    if (!$request['author'] && !$request['parent']) {
        $prepared_args['hierarchical'] = true;
    }
    return $prepared_args;
}


class WWA_REST_Login_Controller extends WP_REST_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'login';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => 'GET, POST',
                'callback'            => array($this, 'get_items'),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'code' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'type' => array(
                'default'           => 'weapp',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function get_items($request) {
        global $wpdb;
        $options = WWA_options();
        $res = array();
        $request['type'] = isset($request['type']) ? $request['type'] : 'weapp';
        switch ($request['type']) {
            case 'swan':
                $str = $this->swan($request);
                $openid = isset($str['openid']) && $str['openid'] ? $str['openid'] : '';
                $meta_key = 'social_type_swan';
                $session_key = 'swan_session_key';
                break;
            case 'alipay':
                $str = $this->alipay($request);
                $openid = isset($str['user_id']) && $str['user_id'] ? $str['user_id'] : '';
                $meta_key = 'social_type_alipay';
                $session_key = 'alipay_session_key';
                break;
            case 'qq':
                $str = $this->qq($request);
                $openid = isset($str['unionid']) && $str['unionid'] ? $str['unionid'] : $str['openid'];
                $meta_key = isset($str['unionid']) && $str['unionid'] ? 'social_type_qq' : 'social_type_qqxcx';
                $session_key = 'qq_session_key';
                break;
            case 'weapp':
            default:
                $str = $this->weapp($request);
                $openid = isset($str['unionid']) && $str['unionid'] ? $str['unionid'] : $str['openid'];
                $meta_key = isset($str['unionid']) && $str['unionid'] ? 'social_type_wechat' : 'social_type_wxxcx';
                $session_key = 'xcx_session_key';
                break;
        }
        if($openid && $meta_key){
            $blog_prefix = $wpdb->get_blog_prefix();
            $users = get_users(
                array(
                    'meta_key' => $blog_prefix . $meta_key,
                    'meta_value' => $openid,
                    'number' => 1
                )
            );
            $user = $users && isset($users[0]) ? $users[0] : '';
            if(!$user && isset($str['unionid']) && $str['unionid']) {
            // 用户不存在，并且有unionid，则向下兼容查询openid是否存在
                $users = get_users(
                    array(
                        'meta_key' => $blog_prefix . 'social_type_'.($request['type']=='qq'?'qq':'wx').'xcx',
                        'meta_value' => $str['openid'],
                        'number' => 1
                    )
                );
                $user = $users && isset($users[0]) ? $users[0] : '';
            }
            if($user && $user->ID){ // 用户已存在
                update_user_option($user->ID, $session_key, $str['session_key']);
                // 开放平台使用unionid的话，需要保存social_type_wxxcx字段方便验证用户
                if($meta_key=='social_type_wechat') update_user_option($user->ID, 'social_type_wxxcx', $openid);
                if($meta_key=='social_type_qq') update_user_option($user->ID, 'social_type_qqxcx', $openid);
                // 有unionid返回，并且meta_key是小程序，则为向下兼容处理查询的用户，需保存social_type_wechat的unionid，更新social_type_wxxcx为unionid
                if($meta_key=='social_type_wxxcx' && isset($str['unionid']) && $str['unionid']){
                    update_user_option($user->ID, 'social_type_wechat', $openid);
                    update_user_option($user->ID, 'social_type_wxxcx', $openid);
                }else if($meta_key=='social_type_qqxcx' && isset($str['unionid']) && $str['unionid']){
                    update_user_option($user->ID, 'social_type_qq', $openid);
                    update_user_option($user->ID, 'social_type_qqxcx', $openid);
                }
                $res['id'] = $user->ID;
                $res['nickname'] = $user->display_name;
                $res['avatar'] = get_avatar_url( $user->ID );
                $res['description'] = $user->description;
                $expire = time() + 24 * HOUR_IN_SECONDS;
                $auth_cookie = $user->ID . ':' . wp_hash_password( $user->ID . ':' . $openid . md5($expire) );
                setcookie('wpcom_rest_token', $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, 1, true);
            }else{
                $userdata = array(
                    'user_pass' => wp_generate_password(),
                    'user_login' => $request['type'] . $openid,
                    'user_email' => $openid . '@' . $request['type'].'.app',
                    'nickname' => $request['nickname'],
                    'display_name' => $request['nickname']
                );

                if(!function_exists('wp_insert_user')){
                    include_once( ABSPATH . WPINC . '/registration.php' );
                }
                $user_id = wp_insert_user($userdata);

                if(!is_wp_error( $user_id ) || (is_wp_error( $user_id ) && isset($user_id->errors['existing_user_login'])) ){
                    if(is_wp_error( $user_id )) {
                        $user = get_user_by( 'email', $openid . '@' . $request['type'].'.app' );
                        if(!$user->ID) return false;
                        $user_id = $user->ID;
                    }
                    wp_update_user( array( 'ID'=>$user_id, 'role'=>'contributor' ) );

                    update_user_option($user_id, $meta_key, $openid);
                    // 开放平台使用unionid的话，需要保存social_type_wxxcx字段方便验证用户
                    if($meta_key=='social_type_wechat') update_user_option($user_id, 'social_type_wxxcx', $openid);
                    if($meta_key=='social_type_qq') update_user_option($user_id, 'social_type_qqxcx', $openid);
                    update_user_option($user_id, $session_key, $str['session_key']);
                    $this->set_avatar($user_id, $request['avatar']);

                    $new_user = get_user_by( 'ID', $user_id );
                    $res['id'] = $user_id;
                    $res['nickname'] = $new_user->display_name;
                    $res['avatar'] = $request['avatar'];
                    do_action('wpcom_social_new_user', $user_id);
                    $expire = time() + 24 * HOUR_IN_SECONDS;
                    $auth_cookie = $user_id . ':' . wp_hash_password( $user_id . ':' . $openid . md5($expire) );
                    setcookie('wpcom_rest_token', $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, 1, true);
                }
            }
        }
        return rest_ensure_response($res);
    }

    function weapp($request) {
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['appid']) ? $options['appid'] : '',
            'secret' => isset($options['secret']) ? $options['secret'] : '',
            'js_code' => $request['code'],
            'grant_type' => 'authorization_code'
        );
        $str = WWA_http_request('https://api.weixin.qq.com/sns/jscode2session', $params, 'POST');
        return $str;
    }

    function qq($request) {
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['qq-appid']) ? $options['qq-appid'] : '',
            'secret' => isset($options['qq-secret']) ? $options['qq-secret'] : '',
            'js_code' => $request['code'],
            'grant_type' => 'authorization_code'
        );
        $str = WWA_http_request('https://api.q.qq.com/sns/jscode2session', $params, 'GET');
        return $str;
    }

    function swan($request) {
        $options = WWA_options();
        $params = array(
            'client_id' => isset($options['swan-key']) ? $options['swan-key'] : '',
            'sk' => isset($options['swan-secret']) ? $options['swan-secret'] : '',
            'code' => $request['code']
        );
        $str = WWA_http_request('https://spapi.baidu.com/oauth/jscode2sessionkey', $params, 'POST');
        return $str;
    }

    function alipay($request) {
        $options = WWA_options();
        $params = array(
            'method' => 'alipay.system.oauth.token',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s"),
            'version' => '1.0',
            'app_id' => isset($options['alipay-appid']) ? $options['alipay-appid'] : '',
            'code' => $request['code'],
            'grant_type' => 'authorization_code'
        );
        $params['sign'] = $this->sign($this->getSignContent($params), 'RSA2');
        $str = WWA_http_request('https://openapi.alipay.com/gateway.do', $params, 'POST');
        return isset($str['alipay_system_oauth_token_response']) ? $str['alipay_system_oauth_token_response'] : array();
    }

    protected function sign($data, $signType = "RSA") {
        $options = WWA_options();
        $priKey = isset($options['alipay-prikey']) ? $options['alipay-prikey'] : '';
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置'); 

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        $sign = base64_encode($sign);
        return $sign;
    }

    function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    function set_avatar($user, $img){
        if(!$user || !$img) return false;

        // 判断是否已经上传头像
        $avatar = get_user_meta( $user, 'wpcom_avatar', 1);
        if ( $avatar != '' ){ //已经设置头像
            return false;
        }

        //Fetch and Store the Image
        $http_options = array(
            'timeout' => 20,
            'redirection' => 20,
            'sslverify' => FALSE
        );

        $get = wp_remote_head( $img, $http_options );
        $response_code = wp_remote_retrieve_response_code ( $get );

        if (200 == $response_code) { // 图片状态需为 200
            $type = $get ['headers'] ['content-type'];

            $mime_to_ext = array(
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
                'image/tiff' => 'tif'
            );

            $file_ext = isset($mime_to_ext[$type]) ? $mime_to_ext[$type] : '';

            $allowed_filetype = array('jpg', 'gif', 'png', 'bmp');

            if (in_array($file_ext, $allowed_filetype)) { // 仅保存图片格式 'jpg','gif','png', 'bmp'
                $http = wp_remote_get($img, $http_options);
                if (!is_wp_error($http) && 200 === $http ['response'] ['code']) { // 请求成功

                    $GLOBALS['image_type'] = 0;

                    $filename = substr(md5($user), 5, 16) . '.' . time() . '.jpg';
                    $mirror = wp_upload_bits( $filename, '', $http ['body'], '1234/06' );

                    if ( !$mirror['error'] ) {
                        $uploads = wp_upload_dir();
                        update_user_meta($user, 'wpcom_avatar', str_replace($uploads['baseurl'], '', $mirror['url']));
                        return $mirror;
                    }
                }
            }
        }
    }
}

function WWA_basic_auth_handler( $user ) {
    global $wp_json_basic_auth_error;

    $wp_json_basic_auth_error = null;

    // Don't authenticate twice
    if ( ! empty( $user ) ) {
        return $user;
    }

    // Check that we're trying to authenticate
    if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
        return $user;
    }

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    /**
     * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
     * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
     * recursion and a stack overflow unless the current function is removed from the determine_current_user
     * filter during authentication.
     */
    remove_filter( 'determine_current_user', 'WWA_basic_auth_handler', 20 );

    $user = wp_authenticate( $username, $password );

    add_filter( 'determine_current_user', 'WWA_basic_auth_handler', 20 );

    if ( is_wp_error( $user ) ) {
        $wp_json_basic_auth_error = $user;
        return null;
    }

    $wp_json_basic_auth_error = true;

    return $user->ID;
}
add_filter( 'determine_current_user', 'WWA_basic_auth_handler', 20 );

function WWA_basic_auth_error( $error ) {
    // Passthrough other errors
    if ( ! empty( $error ) ) {
        return $error;
    }

    global $wp_json_basic_auth_error;

    return $wp_json_basic_auth_error;
}
add_filter( 'rest_authentication_errors', 'WWA_basic_auth_error' );


add_filter( 'authenticate', 'WWA_rest_authenticate', 100 );
function WWA_rest_authenticate($user){
    if( ($user == null || is_wp_error($user)) && isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW'] ){
        $auth_pw = explode(':', $_SERVER['PHP_AUTH_PW']);
        $password = isset($auth_pw[0]) ? $auth_pw[0] : '';
        $expire = isset($auth_pw[1]) ? $auth_pw[1] : '';

        if ($password && $expire && $expire > $_SERVER['REQUEST_TIME']) { // 未过期
            global $wp_hasher;
            if ( empty($wp_hasher) ) {
                require_once( ABSPATH . WPINC . '/class-phpass.php');
                $wp_hasher = new PasswordHash(8, true);
            }
            $get_user = get_user_by( 'ID', $_SERVER['PHP_AUTH_USER'] );
            $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : 'weapp';
            $type = isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : $type;
            switch ($type) {
                case 'swan':
                    $meta_key = 'social_type_swan';
                    break;
                case 'alipay':
                    $meta_key = 'social_type_alipay';
                    break;
                case 'qq':
                    $meta_key = 'social_type_qqxcx';
                    break;
                case 'weapp':
                default:
                    $meta_key = 'social_type_wxxcx';
                    break;
            }
            $openid = get_user_option($meta_key, $get_user->ID);
            if($wp_hasher->CheckPassword($_SERVER['PHP_AUTH_USER'].':'.$openid . md5($expire), $password)) $user = $get_user;
        }
    }
    return $user;
}


add_filter( 'rest_prepare_post', 'WWA_rest_prepare_post', 10, 3 );
add_filter( 'rest_prepare_qa_post', 'WWA_rest_prepare_post', 10, 3 );
function WWA_rest_prepare_post( $data, $post, $request ) {
    $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : '');
    if(!$type) return $data;
    
    $_data = $data->data;
    $params = $request->get_params(); 
    unset( $_data['featured_media'] );
    unset( $_data['format'] );
    unset( $_data['ping_status'] );
    unset( $_data['comment_status'] );
    unset( $_data['template'] );
    unset( $_data['categories'] );
    unset( $_data['guid'] );
    unset( $_data['link'] );
    unset( $_data['special'] );
    unset( $_data['slug'] );
    unset( $_data['tags'] );
    unset( $_data['modified'] );
    unset( $_data['meta'] );
    unset( $_data['date'] );
    if ( !$request['id'] ) { // 无ID则不显示内容
        unset( $_data['content'] );
    }

    foreach($data->get_links() as $_linkKey => $_linkVal) {
        $data->remove_link($_linkKey);
    }

    $data->data = $_data;
    return $data;
}


class WWA_REST_Like_Controller extends WP_REST_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'like';
    }
    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'set_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' )
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'id' => array(
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function set_data($request) {
        $user = wp_get_current_user();
        $res = array();
        if(!$user->ID) {
            $res['code'] = -1;
            $res['msg'] = '请登录后操作！';
        } else if(!$request['id']) {
            $res['code'] = -2;
            $res['msg'] = '文章参数错误！';
        } else {
            $post = get_post($request['id']);
            if($post){
                // 用户关注的文章
                $u_favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
                $u_favorites = $u_favorites ? $u_favorites : array();
                // 文章关注人数
                $p_favorite = get_post_meta($post->ID, 'wpcom_favorites', true);
                $p_favorite = $p_favorite ? $p_favorite : 0;
                if(in_array($post->ID, $u_favorites)){ // 用户是否关注本文
                    $res['code'] = 1;
                    $nu_favorites = array();
                    foreach($u_favorites as $uf){
                        if($uf != $post->ID){
                            $nu_favorites[] = $uf;
                        }
                    }
                    $p_favorite -= 1;
                }else{
                    $res['code'] = 0;
                    $u_favorites[] = $post->ID;
                    $nu_favorites = $u_favorites;
                    $p_favorite += 1;
                }
                $p_favorite = $p_favorite<0 ? 0 : $p_favorite;
                $u = update_user_meta($user->ID, 'wpcom_favorites', $nu_favorites);
                update_post_meta($post->ID, 'wpcom_favorites', $p_favorite);
                $res['likes'] = $p_favorite;
            }else{
                $res['code'] = -3;
                $res['msg'] = '文章信息查询失败！';
            }
        }
        return rest_ensure_response($res);
    }
    public function get_data($request) {
        $user = wp_get_current_user();
        $res = array();
        if(!$user->ID) {
            $res['code'] = -1;
        } else if(!$request['id']) {
            $res['code'] = -2;
        } else {
            $post = get_post($request['id']);
            if($post){
                $res['code'] = 0;
                // 用户关注的文章
                $u_favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
                $u_favorites = $u_favorites ? $u_favorites : array();
                // 文章关注人数
                $p_favorite = get_post_meta($post->ID, 'wpcom_favorites', true);
                $p_favorite = $p_favorite ? $p_favorite : 0;
                $res['likes'] = $p_favorite;

                if(in_array($post->ID, $u_favorites)){ // 用户是否关注本文
                    $res['code'] = 1;
                }
            }else{
                $res['code'] = -3;
            }
        }
        return rest_ensure_response($res);
    }
    public function permission_check(){
        if ( get_current_user_id() ) {
            return true;
        } else {
            return new WP_Error( 'rest_user_cannot_view', '请登录后操作！', array( 'status' => rest_authorization_required_code() ) );
        }
    }
}

class WWA_REST_Post_Likes_Controller extends WP_REST_Controller {
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'post-likes';
    }
    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' ),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'page' => array(
                'default'           => 1,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'default'           => 10,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function get_data($request) {
        $user = wp_get_current_user();
        $posts = array();
        if($user->ID) {
            // 用户关注的文章
            $favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
            $favorites = $favorites ? $favorites : array();
            if($favorites) {
                add_filter('posts_orderby', array($this, 'favorites_posts_orderby'));
                $arg = array(
                    'post_type' => 'post',
                    'posts_per_page' => $request['per_page'],
                    'post__in' => $favorites,
                    'paged' => $request['page'],
                    'ignore_sticky_posts' => 1
                );

                $query_result = new WP_Query($arg);
                global $post;
                while ( $query_result->have_posts() ) {
                    $query_result->the_post();
                    $data = array();
                    $data['id'] = $post->ID;
                    if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
                        $post_date_gmt = get_gmt_from_date( $post->post_date );
                    } else {
                        $post_date_gmt = $post->post_date_gmt;
                    }
                    $data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );
                    $data['title'] = array(
                        'rendered' => get_the_title( $post->ID ),
                    );
                    $excerpt = apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $post ) );
                    $data['excerpt'] = array(
                        'rendered'  => post_password_required( $post ) ? '' : $excerpt,
                        'protected' => (bool) $post->post_password,
                    );
                    $data['author'] = (int) $post->post_author;
                    $data['sticky'] = is_sticky( $post->ID );
                    $data['type'] = $post->post_type;
                    $data['wpcom_metas'] = WWA_rest_post_metas($data, '', $request);
                    $posts[] = $data;
                }
            }
        }
        return rest_ensure_response($posts);
    }
    public function permission_check(){
        if ( get_current_user_id() ) {
            return true;
        } else {
            return new WP_Error( 'rest_user_cannot_view', '请登录后操作！', array( 'status' => rest_authorization_required_code() ) );
        }
    }
    public function favorites_posts_orderby(){
        global $wpdb, $profile;
        $favorites = get_user_meta( get_current_user_id(), 'wpcom_favorites', true );
        if($favorites)
            return "FIELD(".$wpdb->posts.".ID, ".implode(',', $favorites).") DESC";
    }
    protected function prepare_date_response( $date_gmt, $date = null ) {
        // Use the date if passed.
        if ( isset( $date ) ) {
            return mysql_to_rfc3339( $date );
        }

        // Return null if $date_gmt is empty/zeros.
        if ( '0000-00-00 00:00:00' === $date_gmt ) {
            return null;
        }

        // Return the formatted datetime.
        return mysql_to_rfc3339( $date_gmt );
    }
}

class WWA_REST_Zan_Controller extends WP_REST_Controller{

    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'dianzan';
    }

    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'set_data'),
                'args'                => $this->get_collection_params()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'id' => array(
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg'
            )
        );
    }
    public function set_data($request) {
        $res = array();
        if(!$request['id']) {
            $res['code'] = -2;
            $res['msg'] = '文章参数错误！';
        } else {
            $post = get_post($request['id']);
            if($post){
                $res['code'] = 0;
                $likes = get_post_meta($post->ID, 'wpcom_likes', true);
                $likes = $likes ? $likes : 0;
                $res['likes'] = $likes + 1;
                // 数据库增加一个喜欢数量
                update_post_meta( $post->ID, 'wpcom_likes', $res['likes'] );
            }else{
                $res['code'] = -3;
                $res['msg'] = '文章信息查询失败！';
            }
        }
        return rest_ensure_response($res);
    }
}


class WWA_REST_Config_Controller extends WP_REST_Controller{

    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'config';
    }

    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_data'),
                'args'                => array()
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_data($request) {
        $options = WWA_options();
        $res = array('max_upload_size' => wp_max_upload_size());
        $res['slides'] = array();
        $slider_num = isset($options['slider_num']) ? $options['slider_num'] : 5;

        if($slider_num){
            // 幻灯片
            $posts = get_posts('posts_per_page='.$slider_num.'&meta_key=_wwa_slide&meta_value=1&post_type=post');
            if($posts){ global $post;foreach ( $posts as $post ) { setup_postdata( $post );
                $res['slides'][] = array(
                    'id' => $post->ID,
                    'img' => WWA_thumbnail_url( $post->ID ),
                    'title' => get_the_title()
                );
            } wp_reset_postdata(); }
        }

        // 首页tab栏目
        $res['cats'] = array();
        $cats = isset($options['cats_id']) && $options['cats_id'] ? $options['cats_id'] : array();
        if($cats){
            foreach($cats as $cat){
                $res['cats'][] = array(
                    'id' => $cat,
                    'name' => get_cat_name($cat)
                );
            }
        }

        // 搜索
        $res['search_placeholder'] = isset($options['search_text']) ? $options['search_text'] : '';
        $res['search_kws'] = isset($options['search_kw']) ? $options['search_kw'] : '';

        $res['related_title'] = isset($options['related_title']) ? $options['related_title'] : '猜你喜欢';

        // 专题
        $res['zt_title'] = isset($options['zt_title']) ? $options['zt_title'] : '专题';
        $res['zt_desc'] = isset($options['zt_desc']) ? $options['zt_desc'] : '';

        // 快讯
        $res['kx_title'] = isset($options['kx_title']) ? $options['kx_title'] : '快讯';
        $res['kx_desc'] = isset($options['kx_desc']) ? $options['kx_desc'] : '';

        // 颜色
        $res['color'] = isset($options['color']) && $options['color'] ? $options['color'] : '#3ca5f6';


        // QAPress
        if(defined('QAPress_VERSION')){
            global $qa_options;
            $res['qa_cats'] = array();
            $qa_cats = isset($qa_options['category']) && $qa_options['category'] ? $qa_options['category'] : array();
            if($qa_cats && $qa_cats[0]){
                foreach ($qa_cats as $cid) {
                    $c = get_term(trim($cid), 'qa_cat');
                    if($c){
                        $res['qa_cats'][] = array(
                            'id' => $c->term_id,
                            'name' => $c->name
                        );
                    }
                }
            }
        }

        // 选项卡
        $tabbar = array();
        if($options['url']){
            foreach ($options['url'] as $i => $url) {
                $type = isset($options['url_type']) && $options['url_type'][$i] ? $options['url_type'][$i] : '0';
                $id = '';
                $origin = '';
                $title = '';
                $item = array();
                switch ($type) {
                    case '1':
                        $page = get_post($options['url_page'][$i]);
                        $url = 'mpage';
                        $id = $options['url_page'][$i];
                        $title = $options['title'][$i] ? $options['title'][$i] : $page->post_title;
                        break;
                    case '2':
                        $term = get_term($options['url_cat'][$i], 'category');
                        $url = 'mterm';
                        $id = $options['url_cat'][$i];
                        $title = $options['title'][$i] ? $options['title'][$i] : $term->name;
                        break;
                    case '3':
                        $term = get_term_by('name', $options['url_tag'][$i], 'post_tag');
                        $url = 'mterm';
                        $id = $term->term_id;
                        $title = $options['title'][$i] ? $options['title'][$i] : $term->name;
                        break;
                    case '0':
                    default:
                        if($url=='kuaixun') $title = '快讯';
                        if($url=='specials') $title = '专题';
                        if($url=='qapress') $title = '问答';
                        if($options['title'][$i]) $title = $options['title'][$i];
                        break;
                }
                if($url=='kuaixun'||$url=='specials'||$url=='qapress') {
                    $id = $url;
                    $origin = 'mothers';
                }
                $item = array(
                    'url' => $url,
                    'text' => $title,
                    'id' => $id,
                    'type' => $type,
                    'origin' => $origin,
                    'iconPath' => isset($options['icon']) && $options['icon'][$i] ? $options['icon'][$i] : '',
                    'selectedIconPath' => isset($options['icon_active']) && $options['icon_active'][$i] ? $options['icon_active'][$i] : ''
                );
                $tabbar[] = $item;
            }
        }
        $res['tabbar'] = $tabbar;

        $ad = array();
        if(isset($options['ad_type']) && $options['ad_type']){
            foreach ($options['ad_type'] as $x => $ad_type) {
                if(!isset($ad[$ad_type])) $ad[$ad_type] = array();
                if(isset($options['ad_id'][$x])) $ad[$ad_type][$options['ad_id'][$x]] = $options['ad_code'][$x];
            }
        }
        $res['ad'] = $ad;

        $res['seo'] = WWA_seo('home');

        return rest_ensure_response($res);
    }
}

class WWA_REST_MYComments_Controller extends WP_REST_Comments_Controller{
    public function __construct() {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'mycomments';
        $this->meta = new WP_REST_Comment_Meta_Fields();
    }
    public function get_items_permissions_check( $request ) {
        $user = wp_get_current_user();
        if(!($user && $user->ID && $request['author'] && $user->ID == $request['author'][0])){
            return new WP_Error( 'rest_forbidden_param', '没有权限', array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }
}

class WP_REST_QAComment_Meta_Fields extends WP_REST_Comment_Meta_Fields {
    public function get_rest_field_type() {
        return 'qacomment';
    }
}

class WWA_REST_Media2_Controller extends WP_REST_Attachments_Controller {
    public function __construct() {
        $this->post_type = 'attachment';
        $this->namespace = 'wp/v2';
        $this->rest_base = 'media2';
        $this->meta = new WP_REST_Post_Meta_Fields( $this->post_type);
    }
    public function create_item( $request ) {

        if ( ! empty( $request['post'] ) && in_array( get_post_type( $request['post'] ), array( 'revision', 'attachment' ), true ) ) {
            return new WP_Error( 'rest_invalid_param', __( 'Invalid parent type.' ), array( 'status' => 400 ) );
        }

        // Get the file via $_FILES or raw data.
        $files   = $request->get_file_params();
        $headers = $request->get_headers();

        if ( ! empty( $files ) ) {
            $file = $this->upload_from_file( $files, $headers );
        } else {
            $file = $this->upload_from_data( $request->get_body(), $headers );
        }

        if ( is_wp_error( $file ) ) {
            return $file;
        }

        $name       = wp_basename( $file['file'] );
        $name_parts = pathinfo( $name );
        $name       = trim( substr( $name, 0, -( 1 + strlen( $name_parts['extension'] ) ) ) );

        $url  = $file['url'];
        $type = $file['type'];
        $file = $file['file'];

        // Include image functions to get access to wp_read_image_metadata().
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // use image exif/iptc data for title and caption defaults if possible
        $image_meta = wp_read_image_metadata( $file );

        if ( ! empty( $image_meta ) ) {
            if ( empty( $request['title'] ) && trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
                $request['title'] = $image_meta['title'];
            }

            if ( empty( $request['caption'] ) && trim( $image_meta['caption'] ) ) {
                $request['caption'] = $image_meta['caption'];
            }
        }

        $attachment                 = $this->prepare_item_for_database( $request );
        $attachment->post_mime_type = $type;
        $attachment->guid           = $url;

        if ( empty( $attachment->post_title ) ) {
            $attachment->post_title = preg_replace( '/\.[^.]+$/', '', wp_basename( $file ) );
        }

        // $post_parent is inherited from $attachment['post_parent'].
        $id = wp_insert_attachment( wp_slash( (array) $attachment ), $file, 0, true );

        if ( is_wp_error( $id ) ) {
            if ( 'db_update_error' === $id->get_error_code() ) {
                $id->add_data( array( 'status' => 500 ) );
            } else {
                $id->add_data( array( 'status' => 400 ) );
            }
            return $id;
        }

        $attachment = get_post( $id );

        /**
         * Fires after a single attachment is created or updated via the REST API.
         *
         * @since 4.7.0
         *
         * @param WP_Post         $attachment Inserted or updated attachment
         *                                    object.
         * @param WP_REST_Request $request    The request sent to the API.
         * @param bool            $creating   True when creating an attachment, false when updating.
         */
        do_action( 'rest_insert_attachment', $attachment, $request, true );

        // Include admin function to get access to wp_generate_attachment_metadata().
        require_once ABSPATH . 'wp-admin/includes/media.php';

        wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

        if ( isset( $request['alt_text'] ) ) {
            update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
        }

        $fields_update = $this->update_additional_fields_for_object( $attachment, $request );

        if ( is_wp_error( $fields_update ) ) {
            return $fields_update;
        }

        $request->set_param( 'context', 'edit' );

        /**
         * Fires after a single attachment is completely created or updated via the REST API.
         *
         * @since 5.0.0
         *
         * @param WP_Post         $attachment Inserted or updated attachment object.
         * @param WP_REST_Request $request    Request object.
         * @param bool            $creating   True when creating an attachment, false when updating.
         */
        do_action( 'rest_after_insert_attachment', $attachment, $request, true );

        $response = $this->prepare_item_for_response( $attachment, $request );
        $response = rest_ensure_response( $response );
        $response->set_status( 200 );

        return $response;
    }
}

class WWA_REST_QAComments_Controller extends WP_REST_Comments_Controller{
    public function __construct() {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'qacomments';
        $this->meta = new WP_REST_QAComment_Meta_Fields();
    }
    public function get_item_schema() {
        $schema = parent::get_item_schema();
        $schema['title'] = 'qacomment';
        return $schema;
    }
    public function get_items_permissions_check( $request ) {
        if ( ! empty( $request['post'] ) ) {
            foreach ( (array) $request['post'] as $post_id ) {
                $post = get_post( $post_id );

                if ( ! empty( $post_id ) && $post && ! $this->check_read_post_permission( $post, $request ) ) {
                    return new WP_Error( 'rest_cannot_read_post', __( 'Sorry, you are not allowed to read the post for this comment.' ), array( 'status' => rest_authorization_required_code() ) );
                } elseif ( 0 === $post_id && ! current_user_can( 'moderate_comments' ) ) {
                    return new WP_Error( 'rest_cannot_read', __( 'Sorry, you are not allowed to read comments without a post.' ), array( 'status' => rest_authorization_required_code() ) );
                }
            }
        }

        if ( ! empty( $request['context'] ) && 'edit' === $request['context'] && ! current_user_can( 'moderate_comments' ) ) {
            return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit comments.' ), array( 'status' => rest_authorization_required_code() ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            $protected_params = array( 'author', 'author_exclude', 'author_email', 'status' );
            $forbidden_params = array();

            foreach ( $protected_params as $param ) {
                if ( 'status' === $param ) {
                    if ( 'approve' !== $request[ $param ] ) {
                        $forbidden_params[] = $param;
                    }
                } elseif ( 'type' === $param ) {
                    if ( 'comment' !== $request[ $param ] ) {
                        $forbidden_params[] = $param;
                    }
                } elseif ( ! empty( $request[ $param ] ) ) {
                    $forbidden_params[] = $param;
                }
            }

            if ( ! empty( $forbidden_params ) ) {
                return new WP_Error( 'rest_forbidden_param', sprintf( __( 'Query parameter not permitted: %s' ), implode( ', ', $forbidden_params ) ), array( 'status' => rest_authorization_required_code() ) );
            }
        }

        return true;
    }

    public function create_item( $request ) {
        if ( ! empty( $request['id'] ) ) {
            return new WP_Error( 'rest_comment_exists', __( 'Cannot create existing comment.' ), array( 'status' => 400 ) );
        }

        // Do not allow comments to be created with a non-default type.
        if ( ! empty( $request['type'] ) && 'answer' !== $request['type'] && 'qa_comment' !== $request['type'] ) {
            return new WP_Error( 'rest_invalid_comment_type', __( 'Cannot create a comment with that type.' ), array( 'status' => 400 ) );
        }

        $prepared_comment = $this->prepare_item_for_database( $request );
        if ( is_wp_error( $prepared_comment ) ) {
            return $prepared_comment;
        }

        $prepared_comment['comment_type'] = $request['type'];

        /*
         * Do not allow a comment to be created with missing or empty
         * comment_content. See wp_handle_comment_submission().
         */
        if ( empty( $prepared_comment['comment_content'] ) ) {
            return new WP_Error( 'rest_comment_content_invalid', __( 'Invalid comment content.' ), array( 'status' => 400 ) );
        }

        // Setting remaining values before wp_insert_comment so we can use wp_allow_comment().
        if ( ! isset( $prepared_comment['comment_date_gmt'] ) ) {
            $prepared_comment['comment_date_gmt'] = current_time( 'mysql', true );
        }

        // Set author data if the user's logged in.
        $missing_author = empty( $prepared_comment['user_id'] )
            && empty( $prepared_comment['comment_author'] )
            && empty( $prepared_comment['comment_author_email'] )
            && empty( $prepared_comment['comment_author_url'] );

        if ( is_user_logged_in() && $missing_author ) {
            $user = wp_get_current_user();

            $prepared_comment['user_id']              = $user->ID;
            $prepared_comment['comment_author']       = $user->display_name;
            $prepared_comment['comment_author_email'] = $user->user_email;
            $prepared_comment['comment_author_url']   = $user->user_url;
        }

        // Honor the discussion setting that requires a name and email address of the comment author.
        if ( get_option( 'require_name_email' ) ) {
            if ( empty( $prepared_comment['comment_author'] ) || empty( $prepared_comment['comment_author_email'] ) ) {
                return new WP_Error( 'rest_comment_author_data_required', __( 'Creating a comment requires valid author name and email values.' ), array( 'status' => 400 ) );
            }
        }

        if ( ! isset( $prepared_comment['comment_author_email'] ) ) {
            $prepared_comment['comment_author_email'] = '';
        }

        if ( ! isset( $prepared_comment['comment_author_url'] ) ) {
            $prepared_comment['comment_author_url'] = '';
        }

        if ( ! isset( $prepared_comment['comment_agent'] ) ) {
            $prepared_comment['comment_agent'] = '';
        }

        $check_comment_lengths = wp_check_comment_data_max_lengths( $prepared_comment );
        if ( is_wp_error( $check_comment_lengths ) ) {
            $error_code = $check_comment_lengths->get_error_code();
            return new WP_Error( $error_code, __( 'Comment field exceeds maximum length allowed.' ), array( 'status' => 400 ) );
        }

        $prepared_comment['comment_approved'] = wp_allow_comment( $prepared_comment, true );

        if ( is_wp_error( $prepared_comment['comment_approved'] ) ) {
            $error_code    = $prepared_comment['comment_approved']->get_error_code();
            $error_message = $prepared_comment['comment_approved']->get_error_message();

            if ( 'comment_duplicate' === $error_code ) {
                return new WP_Error( $error_code, $error_message, array( 'status' => 409 ) );
            }

            if ( 'comment_flood' === $error_code ) {
                return new WP_Error( $error_code, $error_message, array( 'status' => 400 ) );
            }

            return $prepared_comment['comment_approved'];
        }

        /**
         * Filters a comment before it is inserted via the REST API.
         *
         * Allows modification of the comment right before it is inserted via wp_insert_comment().
         * Returning a WP_Error value from the filter will shortcircuit insertion and allow
         * skipping further processing.
         *
         * @since 4.7.0
         * @since 4.8.0 `$prepared_comment` can now be a WP_Error to shortcircuit insertion.
         *
         * @param array|WP_Error  $prepared_comment The prepared comment data for wp_insert_comment().
         * @param WP_REST_Request $request          Request used to insert the comment.
         */
        $prepared_comment = apply_filters( 'rest_pre_insert_comment', $prepared_comment, $request );
        if ( is_wp_error( $prepared_comment ) ) {
            return $prepared_comment;
        }

        $comment_id = wp_insert_comment( wp_filter_comment( wp_slash( (array) $prepared_comment ) ) );

        if ( ! $comment_id ) {
            return new WP_Error( 'rest_comment_failed_create', __( 'Creating comment failed.' ), array( 'status' => 500 ) );
        }

        if ( isset( $request['status'] ) ) {
            $this->handle_status_param( $request['status'], $comment_id );
        }

        $comment = get_comment( $comment_id );

        /**
         * Fires after a comment is created or updated via the REST API.
         *
         * @since 4.7.0
         *
         * @param WP_Comment      $comment  Inserted or updated comment object.
         * @param WP_REST_Request $request  Request object.
         * @param bool            $creating True when creating a comment, false
         *                                  when updating.
         */
        do_action( 'rest_insert_comment', $comment, $request, true );

        $schema = $this->get_item_schema();

        if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
            $meta_update = $this->meta->update_value( $request['meta'], $comment_id );

            if ( is_wp_error( $meta_update ) ) {
                return $meta_update;
            }
        }

        $fields_update = $this->update_additional_fields_for_object( $comment, $request );

        if ( is_wp_error( $fields_update ) ) {
            return $fields_update;
        }

        $context = current_user_can( 'moderate_comments' ) ? 'edit' : 'view';
        $request->set_param( 'context', $context );

        /**
         * Fires completely after a comment is created or updated via the REST API.
         *
         * @since 5.0.0
         *
         * @param WP_Comment      $comment  Inserted or updated comment object.
         * @param WP_REST_Request $request  Request object.
         * @param bool            $creating True when creating a comment, false
         *                                  when updating.
         */
        do_action( 'rest_after_insert_comment', $comment, $request, true );

        $response = $this->prepare_item_for_response( $comment, $request );
        $response = rest_ensure_response( $response );

        $response->set_status( 201 );
        $response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $comment_id ) ) );

        return $response;
    }
}

function WWA_options(){
    return get_option('wwa_options');
}

function WWA_thumbnail_url($post_id='', $size='full'){
    global $post;
    if(!$post_id) $post_id = isset($post->ID) && $post->ID ? $post->ID : '';
    $img = get_the_post_thumbnail_url($post_id, $size);
    if( !$img ){
        if( !$post || $post->ID!=$post_id){
            $post = get_post($post_id);
        }
        ob_start();
        echo do_shortcode( $post->post_content );
        $content = ob_get_contents();
        ob_end_clean();
        preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $content, $matches);
        if(isset($matches[1]) && isset($matches[1][0])) { // 文章有图片
            $img = $matches[1][0];
        }
    }
    return $img;
}

function WWA_http_request($url, $body=array(), $method='GET'){
    $result = wp_remote_request($url, array('method' => $method, 'body'=>$body));
    if( is_array($result) ){
        $json_r = json_decode($result['body'], true);
        if( !$json_r ){
            parse_str($result['body'], $json_r);
            if( count($json_r)==1 && current($json_r)==='' ) return $result['body'];
        }
        return $json_r;
    }
}

function WWA_weapp_get_access_token(){
    $options = WWA_options();
    $access_token = json_decode(get_option('weapp_access_token'), true);
    if($access_token && $access_token['expires_in'] > time()){
        return $access_token['access_token'];
    }else{
        $params = array(
            'appid' => isset($options['appid']) ? $options['appid'] : '',
            'secret' => isset($options['secret']) ? $options['secret'] : '',
            'grant_type' => 'client_credential'
        );
        $str = WWA_http_request('https://api.weixin.qq.com/cgi-bin/token', $params, 'GET');
        if($str && isset($str['access_token']) && $str['access_token']){
            $str['expires_in'] = $str['expires_in']+time();
            update_option('weapp_access_token', json_encode($str));
            return $access_token['access_token'];
        }
    }
}

function WWA_weapp_msg_sec_check($content){
    $access_token = WWA_weapp_get_access_token();
    if($access_token){
        $params = array(
            'content' => strip_tags($content)
        );
        $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
        $str = WWA_http_request('https://api.weixin.qq.com/wxa/msg_sec_check?access_token='.$access_token, $params, 'POST');
        if($str && isset($str['errcode']) && $str['errcode'] == '87014'){
            return false;
        }
    }
    return true;
}


function WWA_qq_get_access_token(){
    $options = WWA_options();
    $access_token = json_decode(get_option('qq_access_token'), true);
    if($access_token && $access_token['expires_in'] > time()){
        return $access_token['access_token'];
    }else{
        $params = array(
            'appid' => isset($options['qq-appid']) ? $options['qq-appid'] : '',
            'secret' => isset($options['qq-secret']) ? $options['qq-secret'] : '',
            'grant_type' => 'client_credential'
        );
        $str = WWA_http_request('https://api.q.qq.com/api/getToken', $params, 'GET');
        if($str && isset($str['access_token']) && $str['access_token']){
            $str['expires_in'] = $str['expires_in']+time();
            update_option('qq_access_token', json_encode($str));
            return $access_token['access_token'];
        }
    }
}

function WWA_qq_msg_sec_check($content){
    $access_token = WWA_qq_get_access_token();
    if($access_token){
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['qq-appid']) ? $options['qq-appid'] : '',
            'access_token' => $access_token,
            'content' => strip_tags($content)
        );
        $str = WWA_http_request('https://api.q.qq.com/api/json/security/MsgSecCheck?access_token='.$access_token, $params, 'POST');
        if($str && isset($str['errCode']) && $str['errCode'] == '87014'){
            return false;
        }
    }
    return true;
}

add_filter('rest_pre_insert_comment', 'WWA_pre_insert_comment');
function WWA_pre_insert_comment($comment){
    $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : '';
    $type = isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : $type;
    if($type){
        $comment['comment_agent'] = $type . '.' . 'app';
        $check = $type == 'qq' ? WWA_qq_msg_sec_check($comment['comment_content']) : WWA_weapp_msg_sec_check($comment['comment_content']);
        if(!$check){
            return new WP_Error( 'rest_comment_content_invalid', '抱歉，评论含有违法违规内容', array( 'status' => 200 ) );
        }
    }
    return $comment;
}

add_filter('rest_pre_insert_qa_post', 'WWA_pre_insert_qa_post');
function WWA_pre_insert_qa_post($post){
    global $qa_options, $wpcomqadb;
    $post->post_status = 'publish';
    // 判断是否需要审核
    if( !current_user_can( 'publish_posts' ) ){
        $moderation = isset($qa_options['question_moderation']) ? $qa_options['question_moderation'] : 0;
        if( $moderation == '1' ){ // 第一次审核
            $user =  wp_get_current_user();
            $user_total = $wpcomqadb->get_questions_total_by_user($user->ID);
            $post->post_status = $user_total ? 'publish' : 'pending';
        }else if( $moderation == '2' ){ // 全部需要审核
            $post->post_status = 'pending';
        }
    }
    return $post;
}


function WWA_seo($type='', $id=''){
    global $options;
    $keywords = '';
    $description = '';

    if(!isset($options['seo'])){
        $options['keywords'] = '';
        $options['description'] = '';
    }
    if ($type=='home') {
        $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($options['keywords']))));
        $description = esc_attr(trim(strip_tags($options['description'])));
        $image = isset($options['wx_thumb']) ? $options['wx_thumb'] : '';
        $title = isset($options['home-title']) ? $options['home-title'] : '';

        if($title=='') {
            $desc = get_bloginfo('description');
            if ($desc) {
                $title = get_option('blogname') . (isset($options['title_sep_home']) && $options['title_sep_home'] ? $options['title_sep_home'] : ' - ') . $desc;
            } else {
                $title = get_option('blogname');
            }
        }
    } else if ($type=='single' && $id) {
        global $post;
        $post = get_post($id);
        $keywords = str_replace('，', ',', esc_attr(trim(strip_tags(get_post_meta( $post->ID, 'wpcom_seo_keywords', true)))));
        if($keywords=='' && $post->post_type ==='post'){
            $post_tags = get_the_tags();
            if ($post_tags) {
                foreach ($post_tags as $tag) {
                    $keywords = $keywords . $tag->name . ",";
                }
            }
            $keywords = rtrim($keywords, ',');
        } else if($keywords=='' && $post->post_type ==='page') {
            $keywords = $post->post_title;
        }else if($post->post_type ==='product'){
            $product_tag = get_the_terms( $post->ID, 'product_tag' );
            if ($product_tag) {
                foreach ($product_tag as $tag) {
                    $keywords = $keywords . $tag->name . ",";
                }
            }
            $keywords = rtrim($keywords, ',');
        }
        $description = esc_attr(trim(strip_tags(get_post_meta( $post->ID, 'wpcom_seo_description', true))));
        if($description=='') {
            if ($post->post_excerpt) {
                $description = esc_attr(strip_tags($post->post_excerpt));
            } else {
                $content = preg_replace("/\[(\/?map.*?)\]/si", "", $post->post_content);

                $content = str_replace(' ', '', trim(strip_tags($content)));
                $content = preg_replace('/\\s+/', ' ', $content );

                $description = utf8_excerpt($content, 200);
            }
        }

        preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
        if(isset($matches[1]) && isset($matches[1][2])){
            $img_url = array(esc_url($matches[1][0]),esc_url($matches[1][1]),esc_url($matches[1][2]));
        } else {
            $img_url = WWA_thumbnail_url($post->ID, 'full');
            if(!$img_url && isset($matches[1]) && isset($matches[1][0])){
                $img_url = esc_url($matches[1][0]);
            }
        }
        $image = $img_url ? $img_url : (isset($options['wx_thumb']) ? $options['wx_thumb'] : '');
        $title = $post->post_title;
    } else if ($type=='term' && $id) {
        $term = get_term( $id, $taxonomy );
        $keywords = get_term_meta( $term->term_id, 'wpcom_seo_keywords', true );
        $keywords = $keywords!='' ? $keywords : $term->name;
        $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($keywords))));

        $description = get_term_meta( $term->term_id, 'wpcom_seo_description', true );
        $description = $description!='' ? $description : term_description($id);
        $description = esc_attr(trim(strip_tags($description)));
        $title = $term->name;
    }

    return array(
        'title' => $title,
        'keywords' => $keywords,
        'description' => $description,
        'image' => $image
    );
}

if ( ! function_exists( 'utf8_excerpt' ) ) :
    function utf8_excerpt($str, $len){
        $str = strip_tags( str_replace( array( "\n", "\r" ), ' ', $str ) );
        if(function_exists('mb_substr')){
            $excerpt = mb_substr($str, 0, $len, 'utf-8');
        }else{
            preg_match_all("/[x01-x7f]|[xc2-xdf][x80-xbf]|xe0[xa0-xbf][x80-xbf]|[xe1-xef][x80-xbf][x80-xbf]|xf0[x90-xbf][x80-xbf][x80-xbf]|[xf1-xf7][x80-xbf][x80-xbf][x80-xbf]/", $str, $ar);
            $excerpt = join('', array_slice($ar[0], 0, $len));
        }

        if(trim($str)!=trim($excerpt)){
            $excerpt .= '...';
        }
        return $excerpt;
    }
endif;

add_action( 'rest_api_init', 'WWA_rest_routes', 100 );
function WWA_rest_routes(){
    global $WWA;
    if($WWA->is_active()){
        $login = new WWA_REST_Login_Controller();
        $login->register_routes();
        $like = new WWA_REST_Like_Controller();
        $like->register_routes();
        $likes = new WWA_REST_Post_Likes_Controller();
        $likes->register_routes();
        $config = new WWA_REST_Config_Controller();
        $config->register_routes();
        $zan = new WWA_REST_Zan_Controller();
        $zan->register_routes();
        $myc = new WWA_REST_MYComments_Controller();
        $myc->register_routes();
        $qac = new WWA_REST_QAComments_Controller();
        $qac->register_routes();
        $media = new WWA_REST_Media2_Controller();
        $media->register_routes();
    }
}