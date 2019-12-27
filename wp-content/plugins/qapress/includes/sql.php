<?php

class QAPress_SQL {
    function __construct(){
        global $QAPress;
        add_action('activate_'. $QAPress->basename, array($this, 'flush_rewrite_rules'));
    }

    function flush_rewrite_rules(){
        flush_rewrite_rules( true );
    }

    function get_questions_total( $cat=0 ){
        global $wp_questions;
        if( $wp_questions ) return $wp_questions->found_posts;
        $arg = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post'
        );
        if( $cat ){
            $arg['tax_query'] = array(
                array(
                    'taxonomy' => 'qa_cat',
                    'terms'    => $cat,
                )
            );
        }

        $wp_questions = new WP_Query;
        $wp_questions->query($arg);
        return $wp_questions->found_posts;
    }

    function get_questions_total_by_user( $user=0 ){
        global $wp_questions_by_user;
        if( $wp_questions_by_user ) return $wp_questions_by_user->found_posts;
        $arg = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post',
            'author' => $user
        );
        $wp_questions_by_user = new WP_Query;
        $wp_questions_by_user->query($arg);
        return $wp_questions_by_user->found_posts;
    }

    function get_questions( $num=20, $paged=1, $cat=0 ){
        global $wp_questions;
        $arg = array(
            'posts_per_page' => $num,
            'paged' => $paged,
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post',
            'orderby' => 'menu_order modified'
        );
        if( $cat ){
            $arg['tax_query'] = array(
                array(
                    'taxonomy' => 'qa_cat',
                    'terms'    => $cat,
                )
            );
        }
        if( $wp_questions ) $wp_questions->query($arg);

        $wp_questions = new WP_Query;
        return $wp_questions->query($arg);
    }

    function get_questions_by_user( $user, $num=20, $paged=1 ){
        global $wp_questions_by_user;
        $arg = array(
            'posts_per_page' => $num,
            'paged' => $paged,
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post',
            'author' => $user,
            'orderby' => 'modified'
        );
        
        if( $wp_questions_by_user ) $wp_questions_by_user->query($arg);

        $wp_questions_by_user = new WP_Query;
        return $wp_questions_by_user->query($arg);
    }

    function get_question( $id ){
        if($id){
            $post = get_post( $id );
            if( $post && $post->post_type =='qa_post' ) return $post;
        }
    }

    function delete_question( $id ){
        if($id){
            return wp_delete_post($id);
        }
    }

    function insert_question($question){
        if(isset($question['ID'])){
            $update = wp_update_post($question);
            if($update) { //更新成功
                return $question['ID'];
            }else{
                return false;
            }
        }else{
            if($id = wp_insert_post($question)){ //插入成功
                return $id;
            }else{
                return false;
            }
        }
    }

    function add_views($id){
        $views = get_post_meta($id, 'views', true);
        if( !function_exists('the_views') ){
            $views = $views ? $views + 1 : 1;
            update_post_meta($id, 'views', $views);
        }
        return $views;
    }

    function get_answers( $id, $num=20, $paged=1, $order='ASC' ){
        if($id){
            $args = array(
                'parent' => 0,
                'post_id' => $id,
                'number' => $num,
                'paged' => $paged,
                'order' => $order,
                'order_by' => 'comment_date',
                'type' => 'answer',
                'status' => 'approve'
            );
            return get_comments( $args );
        }
    }

    function get_answers_by_user( $user, $num=20, $paged=1, $order='DESC' ){
        if($user){
            $args = array(
                'parent' => 0,
                'user_id' => $user,
                'number' => $num,
                'paged' => $paged,
                'order' => $order,
                'order_by' => 'comment_date',
                'type' => 'answer',
                'status' => 'approve'
            );
            return get_comments( $args );
        }
    }

    function get_answers_total_by_user( $user ){
        if($user){
            $args = array(
                'parent' => 0,
                'user_id' => $user,
                'count'   => true,
                'type' => 'answer',
                'status' => 'approve'
            );
            return get_comments( $args );
        }
    }

    function delete_answers( $question ){
        if($question){
            global $wpdb;
            return $wpdb->delete($wpdb->comments, array('comment_post_ID' => $question));
        }
    }

    function delete_answer( $id ){
        if($id){
            global $wpdb;
            $question = $wpdb->get_var("SELECT comment_post_ID FROM `$wpdb->comments` WHERE comment_ID = '$id'");
            wp_delete_comment($id);
            if($question){
                $answers = $this->get_answers($question, 1, 1, 'DESC');
                if($answers && isset($answers[0]->user_id)){
                    $last_answer = $answers[0]->user_id;
                }else{
                    $last_answer = '';
                }
                $wpdb->update($wpdb->posts, array('post_mime_type' => $last_answer), array('ID' => $question));
            }
        }
    }

    function get_comments($id){
        if($id){
            $args = array(
                'parent' => $id,
            );
            return get_comments( $args );
        }
    }

    function delete_comments( $answer ){
        if($answer){
            global $wpdb;
            return $wpdb->delete($wpdb->comments, array('comment_parent' => $answer));
        }
    }

    function delete_comment( $id ){
        if($id){
            global $wpdb;
            $answer = $wpdb->get_var("SELECT comment_parent FROM `$wpdb->comments` WHERE comment_ID = '$id'");
            $wpdb->delete($wpdb->comments, array('comment_ID' => $id));
            if($answer){
                $cms_total = $wpdb->get_var("SELECT COUNT(comment_ID) FROM `$wpdb->comments` WHERE comment_parent = '$answer'");
                $wpdb->update($wpdb->comments, array('comment_karma' => $cms_total), array('comment_ID' => $answer));
            }
        }
    }

    function insert_comment($comment){
        global $wpdb;
        $cid = wp_insert_comment($comment);
        if($cid){ //插入成功
            $cms_total = count($this->get_comments($comment['comment_parent']));
            $wpdb->update($wpdb->comments, array( 'comment_karma' => $cms_total ), array('comment_ID' => $comment['comment_parent']));
            return $cid;
        }else{
            return false;
        }
    }

    function insert_answer($answer){
        if($answer){
            $id = wp_insert_comment($answer);
            $args = array(
                'ID' => $answer['comment_post_ID'],
                'post_modified' => current_time('mysql'),
                'post_mime_type' => $answer['user_id']
            );
            wp_update_post($args);
            return $id;
        }
    }

    function set_top( $question ){
        if($question){
            global $wpdb;
            $flag = $wpdb->get_var("SELECT menu_order FROM `$wpdb->posts` WHERE ID = '$question'");
            $flag = $flag=='1' ? 0 : 1;
            return $wpdb->update($wpdb->posts, array('menu_order'=>$flag ), array('ID' => $question));
        }
    }
}

$wpcomqadb = new QAPress_SQL();
