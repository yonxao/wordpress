<?php
defined( 'ABSPATH' ) || exit;

class WPCOM {
    public static function get_post($id, $type='post'){
        if(is_numeric($id)){
            return get_post($id);
        }else{
            $args = array(
                'name'        => $id,
                'post_status' => 'any',
                'post_type' => $type,
                'posts_per_page' => 1
            );
            $my_posts = get_posts($args);
            if($my_posts) return $my_posts[0];
        }
    }

    public static function category( $tax = 'category', $filter = false ){
        $args = array(
            'taxonomy' => $tax,
            'hide_empty' => false
        );
        if($filter) $args['suppress_filter'] = true;
        $categories = get_terms($args);

        $cats = array();
        if( $categories && !is_wp_error($categories) ) {
            foreach ($categories as $cat) {
                $cats[$cat->term_id] = $cat->name;
            }
        }

        return $cats;
    }

    public static function get_all_sliders(){
        $sliders = array();
        if(shortcode_exists("rev_slider")){
            $slider = new RevSlider();
            $revolution_sliders = $slider->getArrSliders();
            foreach ( $revolution_sliders as $revolution_slider ) {
                $alias = $revolution_slider->getAlias();
                $title = $revolution_slider->getTitle();
                $sliders[$alias] = $title.' ('.$alias.')';
            }
        }
        return $sliders;
    }

    public static function panel_script(){
        global $pagenow;
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style("themer-panel", FRAMEWORK_URI."/assets/css/panel.css", false, FRAMEWORK_VERSION, "all");
        wp_enqueue_script("vue", FRAMEWORK_URI."/assets/js/vue.min.js", array(), '2.5.22', true);
        wp_enqueue_script("vue-select", FRAMEWORK_URI."/assets/js/vue-select.js", array('vue'), '3.2.0', true);
        wp_enqueue_script("themer-panel", FRAMEWORK_URI . "/assets/js/panel.js", array('jquery', 'jquery-ui-core', 'wp-color-picker', 'jquery-ui-sortable', 'vue-select'), FRAMEWORK_VERSION, true);
        if($pagenow!=='post.php' && $pagenow!=='post-new.php') wp_enqueue_media();
        $settings = wp_get_code_editor_settings( array( 'type' => 'text/html' ) );

        wp_enqueue_script( 'code-editor' );
        wp_enqueue_style( 'code-editor' );
        wp_enqueue_script( 'csslint' );
        wp_enqueue_script( 'htmlhint' );
        wp_enqueue_script( 'jshint' );
        wp_enqueue_script( 'jsonlint' );

        wp_add_inline_script( 'code-editor', sprintf( 'codemirrorSettings = %s', wp_json_encode( $settings ) ) );
    }

    public static function editor_settings($args = array()){
        return array(
            'textarea_name' => $args['textarea_name'],
            'textarea_rows' => isset($args['textarea_rows']) ? $args['textarea_rows'] : 3,
            'tinymce'       => array(
                'height'        => 150,
                'toolbar1' => 'formatselect,fontsizeselect,bold,blockquote,forecolor,alignleft,aligncenter,alignright,link,bullist,numlist,fullscreen',
                'toolbar2' => '',
                'toolbar3' => '',
            )
        );
    }

    public static function _options(){
        $res = array();
        if( current_user_can( 'publish_posts' ) ){
            if(current_user_can( 'edit_theme_options' )) {
                global $wpcom_panel;
                $wpcom_panel->updated(0);
            }
            $res['o'] = get_option( THEME_ID . '_options' );
        }
        echo json_encode($res);
        exit;
    }

    public static function _icons(){
        $res = array();
        if( current_user_can( 'publish_posts' ) ){
            $icons_file = get_template_directory() . '/fonts/icons.json';
            if( file_exists($icons_file) ) {
                $res['icons'] = json_decode(@file_get_contents($icons_file));
            }
        }
        echo json_encode($res);
        exit;
    }

    public static function load( $folder ){
        if( $globs = glob( "{$folder}/*.php" ) ) {
            $config_file = get_template_directory() . '/themer-config.json';
            if( file_exists($config_file) ) {
                $config = @file_get_contents($config_file);
                if( $config != '' ) $config = json_decode($config);
            }
            foreach( $globs as $file ) {
                if( !(isset($config) && isset($config->except) && in_array(str_replace(FRAMEWORK_PATH, 'themer', $file), $config->except)) ){
                    require_once $file;
                }
            }
        }
    }

    public static function thumbnail( $url, $width = null, $height = null, $crop = false, $img_id = 0, $size = '', $single = false, $upscale = true ) {
        /* WPML Fix */
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ){
            global $sitepress;
            $url = $sitepress->convert_url( $url, $sitepress->get_default_language() );
        }
        /* WPML Fix */

        $aq_resize = Aq_Resize::getInstance();
        return $aq_resize->process( $url, $width, $height, $crop, $img_id, $size, $single, $upscale );
    }

    public static function thumbnail_url($post_id='', $size='full', $local=false){
        global $post, $options;
        if(!$post_id) $post_id = isset($post->ID) && $post->ID ? $post->ID : '';
        $img = get_the_post_thumbnail_url($post_id, $size);
        if( !$img ){
            if( !$post || $post->ID!=$post_id){
                $post = get_post($post_id);
            }
            preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
            if(isset($matches[1]) && isset($matches[1][0])) { // 文章有图片
                $img = $matches[1][0];
                if( current_user_can( 'manage_options' ) && isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1' ) {
                    $img = self::save_remote_img($img, $post);
                    if (is_array($img) && isset($img['id'])) {
                        $post_thumbnail_id = $img['id'];
                        $img = $img['url'];
                    }
                    if (!isset($post_thumbnail_id)) $post_thumbnail_id = self::get_attachment_id($img);
                    if (isset($post_thumbnail_id) && $post_thumbnail_id) set_post_thumbnail($post_id, $post_thumbnail_id);
                }
            }

            if($img) {
                $image_sizes = apply_filters('wpcom_image_sizes', array());
                if($size && isset($image_sizes[$size])) {
                    $width = isset($image_sizes[$size]['width']) && $image_sizes[$size]['width'] ? $image_sizes[$size]['width'] : 480;
                    $height = isset($image_sizes[$size]['height']) && $image_sizes[$size]['height'] ? $image_sizes[$size]['height'] : 320;
                    $image = self::thumbnail($img, $width, $height, true, isset($post_thumbnail_id) ? $post_thumbnail_id : 0, $size);
                    if(isset($image[0])) {
                        $img = $image[0];
                    } else if($local) {
                        $img = '';
                    }
                }
            }
        }
        return $img;
    }

    public static function thumbnail_html($html, $post_id, $post_thumbnail_id, $size){
        global $options;
        $img_url = '';
        if( !$post_thumbnail_id ) $img_url = self::thumbnail_url($post_id, $size, true);
        $img_url = apply_filters('wpcom_thumbnail_url', $img_url, $post_id, $post_thumbnail_id, $size);
        if($img_url) {
            $image_sizes = apply_filters('wpcom_image_sizes', array());
            $width = isset($image_sizes[$size]) && isset($image_sizes[$size]['width']) && $image_sizes[$size]['width'] ? $image_sizes[$size]['width'] : 480;
            $height = isset($image_sizes[$size]) && isset($image_sizes[$size]['height']) && $image_sizes[$size]['height'] ? $image_sizes[$size]['height'] : 320;
            if( !self::is_spider() && (!isset($options['thumb_img_lazyload']) || $options['thumb_img_lazyload']=='1') ) { // 非蜘蛛，并且开启了延迟加载
                $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI.'/assets/images/lazy.png';
                $lazy = self::thumbnail($lazy_img, $width, $height, true, 0, $size);
                if($lazy && isset($lazy[0])) $lazy_img = $lazy[0];
                $html = '<img class="j-lazy" src="'.$lazy_img.'" data-original="' . $img_url . '" width="' . $width . '" height="' . $height . '" alt="' . esc_attr(get_the_title($post_id)) . '">';
            } else {
                $html = '<img src="' . $img_url . '" width="' . $width . '" height="' . $height . '" alt="' . esc_attr(get_the_title($post_id)) . '">';
            }
        }
        return $html;
    }

    public static function thumbnail_src($image, $attachment_id, $size, $icon){
        // 排除后台的ajax请求
        if( defined('DOING_AJAX') && DOING_AJAX && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], '/wp-admin/')){
            return $image;
        }

        // 如采用阿里云oss、腾讯云、七牛图片处理缩略图则直接返回
        if( preg_match( '/\?x-oss-process=/i', $image[0]) || preg_match( '/\?imageView2\//i', $image[0]) ){
            return $image;
        }

        $image_sizes = apply_filters('wpcom_image_sizes', array());
        $res_image = '';

        if( is_array($size) ) {
            foreach ($image_sizes as $key => $sizes) {
                if ($sizes['width'] == $size[0] && $sizes['height'] == $size[1]) {
                    $size = $key;
                }
            }
        }

        if( !is_array($size) && isset($image_sizes[$size]) && !is_admin() ){
            $img_url = wp_get_attachment_url($attachment_id);
            $res_image = self::thumbnail($img_url, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, $attachment_id, $size);
            // 裁剪失败，则返回原数据
            if( isset($res_image[0]) && $res_image[0]==$img_url ) $res_image = $image;
        }
        return $res_image ? $res_image : $image;
    }

    public static function thumbnail_attr($attr, $attachment, $size){
        global $options, $post;

        if( self::is_spider() || (isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='0') ) {
            $attr['alt'] = isset($post->post_title) && $post->post_title ? $post->post_title : $attachment->post_title;
            return $attr;
        }

        $image_sizes = apply_filters('wpcom_image_sizes', array());
        if( (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) && !is_embed() ) {
            // 排除后台的ajax请求
            if( defined('DOING_AJAX') && DOING_AJAX && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], '/wp-admin/')){
                return $attr;
            }

            $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI . '/assets/images/lazy.png';
            if( !is_array($size) && isset($image_sizes[$size]) ) {
                $lazy = self::thumbnail($lazy_img, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, 0, $size);
                if ($lazy && isset($lazy[0])) $lazy_img = $lazy[0];
            }
            $attr['data-original'] = $attr['src'];
            $attr['src'] = $lazy_img;
            $attr['class'] .= ' j-lazy';
            $attr['alt'] = isset($post->post_title) ? $post->post_title : $attachment->post_title;
        }
        return $attr;
    }

    public static function check_post_images( $new_status, $old_status, $_post ){
        global $wpcom_panel, $post;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            global $options, $wpdb;
            if ($new_status != 'publish') return false;
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
            if (defined('DOING_AJAX') && DOING_AJAX) return false;
            if (!isset($post->ID)) $post = $_post;
            if ($post->ID !== $_post->ID) return false;

            // post 文章类型检查缩略图
            if ( (!isset($options['save_remote_img']) || $options['save_remote_img'] == '0') &&
                isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1' &&
                $_post->post_type == 'post') {
                $post_thumbnail_id = get_post_meta($_post->ID, '_thumbnail_id', true);
                if (!$post_thumbnail_id) {
                    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $_post->post_content, $matches);
                    if (isset($matches[1]) && isset($matches[1][0])) {
                        $img_url = $matches[1][0];
                        self::save_remote_img($img_url, $_post);
                    }
                }
            } else if (isset($options['save_remote_img']) && $options['save_remote_img'] == '1') {
                set_time_limit(0);
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $_post->post_content, $matches);

                $search = array();
                $replace = array();
                if (isset($matches[1]) && isset($matches[1][0])) {
                    $feature = 0;
                    $post_thumbnail_id = get_post_meta($_post->ID, '_thumbnail_id', true);

                    // 文章无特色图片，并开启了自动特色图片
                    if ($_post->post_type == 'post' && !$post_thumbnail_id && isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1') $feature = 1;

                    // 去重
                    $image_list = array();
                    foreach ($matches[1] as $item) {
                        if (!in_array($item, $image_list)) array_push($image_list, $item);
                    }

                    $i = 0;
                    foreach ($image_list as $img) {
                        $img_url = self::save_remote_img($img, $_post, $i == 0 && $feature);
                        $is_except = 0;

                        if( $i == 0 && $feature && isset($options['remote_img_except']) && trim($options['remote_img_except']) != '' ){ // 第一张是白名单图片的话可以不用替换原文的图片地址
                            $excepts = explode("\r\n", trim($options['remote_img_except']) );
                            if( $excepts ) {
                                foreach ($excepts as $except) {
                                    if (trim($except) && false !== stripos($img, trim($except))) {
                                        $is_except = 1;
                                        break;
                                    }
                                }
                            }
                        }

                        if (!$is_except && is_array($img_url) && isset($img_url['id'])) {
                            array_push($search, $img);
                            array_push($replace, $img_url['url']);
                        }
                        $i++;
                    }

                    if ($search) {
                        $_post->post_content = str_replace($search, $replace, $_post->post_content);
                        // wp_update_post(array('ID' => $_post->ID, 'post_content' => $_post->post_content));
                        // wp_update_post会重复触发 transition_post_status hook
                        $data = array('post_content' => $_post->post_content);
                        $data = wp_unslash($data);
                        $wpdb->update($wpdb->posts, $data, array('ID' => $_post->ID));
                    }
                }
            }
        }
    }

    public static function save_remote_img($img_url, $post=null, $feature = 1){
        if( $feature==0 ){ // 非特色图片的时候，需要另外判断白名单
            global $options;
            if( isset($options['remote_img_except']) && trim($options['remote_img_except']) != '' ){
                $excepts = explode("\r\n", trim($options['remote_img_except']) );
                if($excepts) {
                    foreach ($excepts as $except) {
                        if (trim($except) && false !== stripos($img_url, trim($except))) {
                            return $img_url;
                        }
                    }
                }
            }
        }

        $upload_info = wp_upload_dir();
        $upload_url = $upload_info['baseurl'];

        $http_prefix = "http://";
        $https_prefix = "https://";
        $relative_prefix = "//"; // The protocol-relative URL

        /* if the $url scheme differs from $upload_url scheme, make them match
           if the schemes differe, images don't show up. */
        if(!strncmp($img_url, $https_prefix,strlen($https_prefix))){ //if url begins with https:// make $upload_url begin with https:// as well
            $upload_url = str_replace($http_prefix, $https_prefix, $upload_url);
        }elseif(!strncmp($img_url, $http_prefix, strlen($http_prefix))){ //if url begins with http:// make $upload_url begin with http:// as well
            $upload_url = str_replace($https_prefix, $http_prefix, $upload_url);
        }elseif(!strncmp($img_url, $relative_prefix, strlen($relative_prefix))){ //if url begins with // make $upload_url begin with // as well
            $upload_url = str_replace(array( 0 => "$http_prefix", 1 => "$https_prefix"), $relative_prefix, $upload_url);
        }

        // Check if $img_url is local.
        if ( false === strpos( $img_url, $upload_url ) ){ // 外链图片
            //Fetch and Store the Image
            $http_options = array(
                'httpversion' => '1.0',
                'timeout' => 30,
                'redirection' => 20,
                'sslverify' => FALSE,
                'user-agent' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; MALC)'
            );

            if( preg_match('/\/\/mmbiz\.qlogo\.cn/i', $img_url) || preg_match('/\/\/mmbiz\.qpic\.cn/i', $img_url) ){ // 微信公众号图片，webp格式图片处理
                $urlarr = parse_url( $img_url );
                if( isset($urlarr['query']) ) parse_str($urlarr['query'],$parr);
                if( isset($parr['wx_fmt']) ) $img_url = str_replace('tp=webp', 'tp='.$parr['wx_fmt'], $img_url);
            }

            if(preg_match('/^\/\//i', $img_url)) $img_url = 'http:' . $img_url;
            $img_url =  wp_specialchars_decode($img_url);
            $get = wp_remote_head( $img_url, $http_options );
            $response_code = wp_remote_retrieve_response_code ( $get );

            if (200 == $response_code) { // 图片状态需为 200
                $type = strtolower($get['headers']['content-type']);

                $mime_to_ext = array (
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/bmp' => 'bmp',
                    'image/tiff' => 'tif'
                );

                $file_ext = isset($mime_to_ext[$type]) ? $mime_to_ext[$type] : '';

                if( $type == 'application/octet-stream' ){
                    $parse_url = parse_url($img_url);
                    $file_ext = pathinfo($parse_url['path'], PATHINFO_EXTENSION);
                    if($file_ext){
                        foreach ($mime_to_ext as $key => $value) {
                            if(strtolower($file_ext)==$value){
                                $type = $key;
                                break;
                            }
                        }
                    }
                }

                $allowed_filetype = array('jpg','gif','png', 'bmp');

                if (in_array ( $file_ext, $allowed_filetype )) { // 仅保存图片格式 'jpg','gif','png', 'bmp'
                    $http = wp_remote_get ( $img_url, $http_options );
                    if (!is_wp_error ( $http ) && 200 === $http ['response'] ['code']) { // 请求成功
                        $filename = rawurldecode(wp_basename(parse_url($img_url,PHP_URL_PATH)));
                        $ext = substr(strrchr($filename, '.'), 1);
                        $filename = wp_basename($filename, "." . $ext) . '.' . $file_ext;

                        $time = $post ? date('Y/m', strtotime($post->post_date)) : date('Y/m');
                        $mirror = wp_upload_bits($filename, '', $http ['body'], $time);

                        // 保存到媒体库
                        $attachment = array(
                            'post_title' => preg_replace( '/\.[^.]+$/', '', $filename ),
                            'post_mime_type' => $type,
                            'guid' => $mirror['url']
                        );

                        $attach_id = wp_insert_attachment($attachment, $mirror['file'], $post?$post->ID:0);

                        if($attach_id) {
                            $attach_data = self::generate_attachment_metadata($attach_id, $mirror['file']);
                            wp_update_attachment_metadata($attach_id, $attach_data);

                            if ($post && $feature) {
                                // 设置文章特色图片
                                set_post_thumbnail($post->ID, $attach_id);
                            }

                            $img_url = array(
                                'id' => $attach_id,
                                'url' => $mirror['url']
                            );
                        }else{ // 保存到数据库失败，则删除图片
                            @unlink($mirror['file']);
                        }
                    }
                }
            }
        }

        return $img_url;
    }

    public static function get_attachment_id( $url ) {
        $attachment_id = 0;
        $dir = wp_upload_dir();
        if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
            $file = wp_basename( parse_url($url, PHP_URL_PATH) );
            $query_args = array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'fields'      => 'ids',
                'meta_query'  => array(
                    array(
                        'value'   => $file,
                        'compare' => 'LIKE',
                        'key'     => '_wp_attachment_metadata',
                    ),
                )
            );
            $query = new WP_Query( $query_args );
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {
                    $meta = wp_get_attachment_metadata( $post_id );
                    $original_file       = basename( $meta['file'] );
                    $cropped_image_files = isset($meta['sizes']) ? wp_list_pluck( $meta['sizes'], 'file' ) : array();
                    if ( $original_file === $file || ($cropped_image_files && in_array( $file, $cropped_image_files )) ) {
                        $attachment_id = $post_id;
                        break;
                    }
                }
            }
        }
        return $attachment_id;
    }

    public static function generate_attachment_metadata($attachment_id, $file) {
        $attachment = get_post ( $attachment_id );
        $metadata = array ();
        if (!function_exists('file_is_displayable_image')) include( ABSPATH . 'wp-admin/includes/image.php' );
        if (preg_match ( '!^image/!', get_post_mime_type ( $attachment ) ) && file_is_displayable_image ( $file )) {
            $imagesize = getimagesize ( $file );
            $metadata ['width'] = $imagesize [0];
            $metadata ['height'] = $imagesize [1];

            // Make the file path relative to the upload dir
            $metadata ['file'] = _wp_relative_upload_path ( $file );

            // Fetch additional metadata from EXIF/IPTC.
            $image_meta = wp_read_image_metadata( $file );
            if ( $image_meta )
                $metadata['image_meta'] = $image_meta;

            // work with some watermark plugin
            $metadata = apply_filters ( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
        }
        return $metadata;
    }

    public static function reg_module( $module ){
        add_action('wpcom_modules_'.$module, 'wpcom_modules_'.$module, 10, 2);
        add_filter('wpcom_modules', 'wpcom_'.$module);
    }

    public static function color( $color, $rgb = false ){
        if($rgb){
            $color = str_replace('#', '', $color);
            if (strlen($color) > 3) {
                $rgb = array(
                    'r' => hexdec(substr($color, 0, 2)),
                    'g' => hexdec(substr($color, 2, 2)),
                    'b' => hexdec(substr($color, 4, 2))
                );
            } else {
                $r = substr($color, 0, 1) . substr($color, 0, 1);
                $g = substr($color, 1, 1) . substr($color, 1, 1);
                $b = substr($color, 2, 1) . substr($color, 2, 1);
                $rgb = array(
                    'r' => hexdec($r),
                    'g' => hexdec($g),
                    'b' => hexdec($b)
                );
            }
            return $rgb;
        }else{
            if(strlen($color) && substr($color, 0, 1)!='#'){
                $color = '#'.$color;
            }
            return $color;
        }
    }

    public static function gradient_color($str){
        $res = '';
        if($str && $color = json_decode($str)){
            $type = $color->d == 4 ? 'radial' : 'linear';
            $angle = '';
            $o_angle = '';
            switch ($color->d) {
                default:
                case 0:
                    $angle = '90deg, ';
                    $o_angle = '0, ';
                    break;
                case 1:
                    $angle = '180deg, ';
                    $o_angle = '-90deg, ';
                    break;
                case 2:
                    $angle = '45deg, ';
                    $o_angle = '45deg, ';
                    break;
                case 3:
                    $angle = '135deg, ';
                    $o_angle = '-45deg, ';
                    break;
                case 4:
                    break;
            }
            $res = 'background-color: '.self::color($color->c1).';';
            foreach (array(' -webkit-',' -o-',' -moz-',' ') as $prefix){
                $_angle = trim($prefix) ? $o_angle : $angle;
                $res .= 'background-image:'.$prefix.$type.'-gradient('.$_angle.self::color($color->c1?$color->c1:'#fff').' 0%, '.self::color($color->c2?$color->c2:'#fff').' 100%);';
            }
        }else{
            $res = 'background-color: '.self::color($str).';';
        }
        return $res;
    }

    public static function shortcode_render(){
        $shortcodes = array('btn', 'gird', 'icon', 'alert', 'panel', 'tabs', 'accordion', 'map');
        foreach($shortcodes as $sc){
            add_shortcode($sc, 'wpcom_sc_'.$sc);
        }
    }

    public static function is_spider() {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $spiders = array(
            'Googlebot', // Google
            'Baiduspider', // 百度
            '360Spider', // 360
            'bingbot', // Bing
            'Sogou web spider' // 搜狗
        );

        foreach ($spiders as $spider) {
            $spider = strtolower($spider);
            //查找有没有出现过
            if (strpos($userAgent, $spider) !== false) {
                return $spider;
            }
        }
    }

    public static function meta_filter( $res, $object_id, $meta_key, $single){
        if ($res) return $res;
        $key = preg_replace('/^wpcom_/i', '', $meta_key);
        if ( $key !== $meta_key ) {
            $filter = current_filter();
            $metas_key = '_wpcom_metas';
            if( $filter=='get_post_metadata' ){
                $meta_type = 'post';
            }else if( $filter=='get_user_metadata' ){
                global $wpdb;
                $metas_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
                $meta_type = 'user';
            }else if( $filter=='get_term_metadata' ){
                $meta_type = 'term';
            }

            // 排除字段直接读取
            $exclude = apply_filters("wpcom_exclude_{$meta_type}_metas", array());
            if(in_array($key, $exclude)) {
                $meta_cache = wp_cache_get( $object_id,  $meta_type . '_meta' );
                if ( ! $meta_cache ) {
                    $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
                    $meta_cache = $meta_cache[ $object_id ];
                }
                if ( isset( $meta_cache[ $meta_key ] ) ) {
                    if ( $single ) {
                        return maybe_unserialize( $meta_cache[ $meta_key ][0] );
                    } else {
                        return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
                    }
                }
            }

            $metas = call_user_func("get_{$meta_type}_meta", $object_id, $metas_key, true);

            //向下兼容
            if( $filter=='get_term_metadata' && $metas=='' ) {
                $term = get_term($object_id);
                if( $term && isset($term->term_id) ) $metas = get_option('_'.$term->taxonomy.'_'.$object_id);
                if( $metas!='' ){
                    update_term_meta( $object_id, '_wpcom_metas', $metas );
                }
            }

            if( isset($metas) && isset($metas[$key]) ) {
                if(in_array($key, $exclude)) {
                    add_metadata($meta_type, $object_id, $meta_key, $metas[$key], $single);
                }
                if( $single && is_array($metas[$key]) )
                    return array( $metas[$key] );
                else if( !$single && empty($metas[$key]) )
                    return array();
                else
                    return array($metas[$key]);
            }
        } else if($meta_key=='_page_modules' && !$res && current_filter()=='get_post_metadata') {
            $meta_cache = wp_cache_get( $object_id,  'post_meta' );
            if ( ! $meta_cache ) {
                $meta_cache = update_meta_cache( 'post', array( $object_id ) );
                $meta_cache = $meta_cache[ $object_id ];
            }
            if ( isset( $meta_cache[ $meta_key ] ) ) {
                $res = maybe_unserialize( $meta_cache[ $meta_key ][0] );
                if(is_string($res)) $res = json_decode($res, true);
                $res = array($res);
            }
        }
        return $res;
    }

    public static function add_metadata($check, $object_id, $meta_key, $meta_value){
        $key = preg_replace('/^wpcom_/i', '', $meta_key);
        if ( $key !== $meta_key ) {
            global $wpdb;
            $filter = current_filter();
            $pre_key = '_wpcom_metas';
            if( $filter=='add_post_metadata' || $filter=='update_post_metadata' ){
                $meta_type = 'post';
            }else if( $filter=='add_term_metadata' || $filter=='update_term_metadata' ){
                $meta_type = 'term';
            }else{
                $pre_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
                $meta_type = 'user';
            }

            $exclude = apply_filters("wpcom_exclude_{$meta_type}_metas", array());
            if(in_array($key, $exclude)) return $check;

            $table = _get_meta_table($meta_type);
            $column = sanitize_key($meta_type . '_id');
            $metas = call_user_func("get_{$meta_type}_meta", $object_id, $pre_key, true);

            $pre_value = '';
            if( $metas ) {
                if( isset($metas[$key]) ) $pre_value = $metas[$key];
                $metas[$key] = $meta_value;
            } else {
                $metas = array(
                    $key => $meta_value
                );
            }

            if( $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d",
                $pre_key, $object_id ) ) ){
                $where = array( $column => $object_id, 'meta_key' => $pre_key );
                $result = $wpdb->update( $table, array('meta_value'=>maybe_serialize($metas)), $where );
            }else{
                $result = $wpdb->insert( $table, array(
                    $column => $object_id,
                    'meta_key' => $pre_key,
                    'meta_value' => maybe_serialize($metas)
                ) );
            }

            if( $result && $meta_value != $pre_value && ($filter=='add_user_metadata' || $filter=='update_user_metadata') ) {
                do_action( 'wpcom_user_meta_updated', $object_id, $meta_key, $meta_value, $pre_value );
            }

            if($result) {
                wp_cache_delete($object_id, $meta_type . '_meta');
                return true;
            }
        }
        return $check;
    }

    public static function kses_allowed_html( $html ){
        if(isset($html['img'])){
            $html['img']['data-original'] = 1;
        }
        return $html;
    }
}


add_filter( 'post_thumbnail_html', array('WPCOM', 'thumbnail_html'), 10, 4 );
add_filter( 'wp_get_attachment_image_src', array('WPCOM', 'thumbnail_src'), 10, 4 );
add_filter( 'wp_get_attachment_image_attributes', array('WPCOM', 'thumbnail_attr'), 20, 3 );
add_filter( 'wp_kses_allowed_html', array('WPCOM', 'kses_allowed_html'), 20 );

add_action( 'init', array('WPCOM', 'shortcode_render') );
add_action( 'wp_ajax_wpcom_options', array( 'WPCOM', '_options') );
add_action( 'wp_ajax_wpcom_icons', array( 'WPCOM', '_icons') );
add_filter( 'get_post_metadata', array( 'WPCOM', 'meta_filter' ), 20, 4 );
add_filter( 'add_post_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'update_post_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'get_user_metadata', array( 'WPCOM', 'meta_filter' ), 20, 4 );
add_filter( 'add_user_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'update_user_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'get_term_metadata', array( 'WPCOM', 'meta_filter' ), 20, 4 );
add_filter( 'add_term_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'update_term_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );

add_action( 'transition_post_status', array('WPCOM', 'check_post_images'), 10, 3 );

$tpl_dir = get_template_directory();
$sty_dir = get_stylesheet_directory();

require FRAMEWORK_PATH . '/core/panel.php';
require FRAMEWORK_PATH . '/core/visual-editor.php';
require FRAMEWORK_PATH . '/core/module.php';
require FRAMEWORK_PATH . '/core/widget.php';

if(is_dir($tpl_dir . '/widgets')) WPCOM::load($tpl_dir . '/widgets');
WPCOM::load(FRAMEWORK_PATH . '/functions');
WPCOM::load(FRAMEWORK_PATH . '/widgets');
WPCOM::load(FRAMEWORK_PATH . '/modules');
WPCOM::load($tpl_dir . '/modules');
if($tpl_dir !== $sty_dir) {
    WPCOM::load($sty_dir . '/modules');
}