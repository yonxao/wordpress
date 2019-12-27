<?php
add_filter( 'rewrite_rules_array','QAPress_rewrite' );
function QAPress_rewrite( $rules ){
    global $qa_slug, $qa_options, $permalink_structure;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    
    $qa_page_id = $qa_options['list_page'];

    if($qa_slug==''){
        $qa_page = get_post($qa_page_id);
        $qa_slug = isset($qa_page->ID) ? $qa_page->post_name : '';
    }

    $newrules = array();
    if($qa_slug){
        if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        $pre = preg_match( '/^\/index\.php\//i', $permalink_structure) ? 'index.php/' : '';

        $newrules[$pre . $qa_slug.'/(\d+)\.html$'] = 'index.php?post_type=qa_post&p=$matches[1]';
        $newrules[$pre . $qa_slug.'/(\d+)?$'] = 'index.php?page_id='.$qa_page_id.'&qa_page=$matches[1]';
        $newrules[$pre . $qa_slug.'/([^/]+)?$'] = 'index.php?page_id='.$qa_page_id.'&qa_cat=$matches[1]';
        $newrules[$pre . $qa_slug.'/([^/]+)/(\d+)?$'] = 'index.php?page_id='.$qa_page_id.'&qa_cat=$matches[1]&qa_page=$matches[2]';
    }

    return $newrules + $rules;
}

add_action( 'init', 'QAPress_single_rewrite' );
function QAPress_single_rewrite() {
    global $wp_rewrite, $options, $permalink_structure, $qa_slug, $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');

    if(!isset($qa_slug) || !$qa_slug ){
        $qa_page_id = $qa_options['list_page'];
        $qa_page = get_post($qa_page_id);
        $qa_slug = isset($qa_page->ID) ? $qa_page->post_name : '';
    }

    if($permalink_structure && $qa_slug){
        $queryarg = 'post_type=qa_post&p=';
        $wp_rewrite->add_rewrite_tag( '%qa_post_id%', '([^/]+)', $queryarg );
        $wp_rewrite->add_permastruct( 'QAPress', $qa_slug.'/%qa_post_id%.html', false );
    }
}

add_filter('post_type_link', 'QAPress_single_permalink', 5, 2);
function QAPress_single_permalink( $post_link, $id ) {
    global $wp_rewrite, $permalink_structure;
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
    if($permalink_structure) {
        $post = get_post($id);
        if (!is_wp_error($post) && $post->post_type == 'qa_post') {
            $newlink = $wp_rewrite->get_extra_permastruct('QAPress');
            $newlink = str_replace('%qa_post_id%', $post->ID, $newlink);
            $newlink = home_url(untrailingslashit($newlink));
            return $newlink;
        }
    }
    return $post_link;
}

add_filter('template_include', 'QAPress_template_include');
function QAPress_template_include($template){
    global $qa_options, $post;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    $qa_page_id = $qa_options['list_page'];
    if(is_singular('qa_post')){
        $tpl_sulg = get_page_template_slug( $qa_page_id );
        $template = get_query_template( 'page', array($tpl_sulg) );
        if($template=='') $template = get_query_template( 'page', array('page.php') );
    }
    return $template;
}

add_filter('the_content', 'QAPress_single_content', 1);
function QAPress_single_content($content){
    global $wp_query;
    if( isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' ){
        $content = '[QAPress]';
    }
    return $content;
}

add_filter('query_vars', 'QAPress_query_vars', 10, 1 );
function QAPress_query_vars($public_query_vars) {
    $public_query_vars[] = 'qa_page';
    $public_query_vars[] = 'qa_cat';

    return $public_query_vars;
}

add_filter('user_trailingslashit', 'QAPress_untrailingslashit', 10, 2);
function QAPress_untrailingslashit($string, $url){
    if(preg_match('/\.html\/$/i', $string)){
        return untrailingslashit($string);
    }
    return $string;
}

function QAPress_category_url($cat, $page=1){
    global $permalink_structure, $wp_rewrite, $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
    
    $qa_page_id = $qa_options['list_page'];

    $page_url = get_permalink($qa_page_id);

    if($permalink_structure){
        $url = trailingslashit($page_url).$cat;
        if($page>1){
            $url = trailingslashit($url).$page;
        }
    }else{
        $url =  $cat ? add_query_arg('qa_cat', $cat, $page_url) : $page_url;
        if($page>1){
            $url = add_query_arg('qa_page', $page, $url);
        }
    }

    if ( $wp_rewrite->use_trailing_slashes )
        $url = trailingslashit($url);
    else
        $url = untrailingslashit($url);

    return $url;
}

function QAPress_edit_url( $qid ){
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    $new_page_id = $qa_options['new_page'];

    $edit_url = get_permalink($new_page_id);

    $edit_url =  add_query_arg('type', 'edit', $edit_url);
    $edit_url =  add_query_arg('id', $qid, $edit_url);

    return $edit_url;
}

add_action( 'template_redirect', 'QAPress_404_page', 1 );
function QAPress_404_page() {
    global $wp_query, $wpcomqadb, $qa_options, $current_cat, $wpdb, $post;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    if( isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' ){
        $qa_single = $post;
        if( ! ( $qa_single && isset($qa_single->ID) ) ){
            $qtable = $wpdb->prefix.'wpcom_questions';
            $id = $wp_query->query['p'];
            $post = $wpdb->get_row("SELECT * FROM `$qtable` WHERE ID = '$id'");
            if($post && $post->flag<0){
                $new_id = -($post->flag);
                if($new_id && $link = get_permalink($new_id) ) {
                    wp_redirect( $link, 301 );
                    exit;
                }
            }

            $wp_query->set_404();
            status_header(404);
            get_template_part( 404 );
            exit();
        }
    }else if(isset($wp_query->query['qa_cat']) && $wp_query->query['qa_cat']){
        if(!$current_cat) $current_cat = get_term_by('slug', $wp_query->query['qa_cat'], 'qa_cat');
        if(!$current_cat){
            $wp_query->set_404();
            status_header(404);
            get_template_part( 404 );
            exit();
        }
    }

    if(isset($wp_query->query['qa_page']) && $wp_query->query['qa_page']){
        $total_q = $wpcomqadb->get_questions_total( isset($current_cat) ? $current_cat->term_id : 0 );
        $per_page = isset($qa_options['question_per_page']) && $qa_options['question_per_page'] ? $qa_options['question_per_page'] : 20;

        $numpages = ceil($total_q/$per_page);
        if($wp_query->query['qa_page']>$numpages){
            $wp_query->set_404();
            status_header(404);
            get_template_part( 404 );
            exit();
        }
    }
}