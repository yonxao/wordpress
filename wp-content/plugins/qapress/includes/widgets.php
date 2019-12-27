<?php

class QAPress_Widget_New extends WP_Widget {

    public function __construct() {
        parent::__construct( 'qapress-new', '#QAPress#发布新帖', array(
            'classname'   => 'widget_qapress_new',
            'description' => '问答插件发布新帖按钮',
        ) );
    }

    public function widget( $args, $instance ) {
        global $qa_options, $wp_query;
        if(!isset($qa_options)) $qa_options = get_option('qa_options');

        $text = empty( $instance['text'] ) ? '发布新帖' : $instance['text'];
        $show = isset($instance['show']) && $instance['show']=='1' ? '1' :  '0';
    
        if($show=='0'){
            $qa_page_id = $qa_options['list_page'];
            if(! (is_page($qa_page_id) || (isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post')) ) return false;
        }
        
        $new_page_id = $qa_options['new_page'];
        $new_url = get_permalink($new_page_id);

        echo $args['before_widget'];
        echo '<a class="q-btn-new" href="'.$new_url.'">'.$text.'</a>';
        echo $args['after_widget'];
    }

    function update( $new_instance, $instance ) {
        $instance['text'] = $new_instance['text'];
        $instance['show'] = $new_instance['show'];
        return $instance;
    }

    function form( $instance ) {
        $text = empty( $instance['text'] ) ? '发布新帖' :  $instance['text'];
        $show = isset($instance['show']) && $instance['show']=='1' ? '1' :  '0';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>">按钮名：</label>
            <input id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>" type="text" value="<?php echo esc_attr( $text ); ?>"><br>
            <span>支持html代码，如网站支持Font Awesome图标，可以填写：<br><code><?php echo esc_attr('<i class="fa fa-edit"></i> 发布新帖');?></code></span>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>">按钮显示：</label>
            <br>
            <input id="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show' ) ); ?>" type="radio" value="0"<?php echo $show=='0'?' checked':''?>>仅问答页面
            <br>
            <input id="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show' ) ); ?>" type="radio" value="1"<?php echo $show=='1'?' checked':''?>>全部显示
        </p>
    <?php
    }
}


class QAPress_Widget_List extends WP_Widget {

    public function __construct() {
        parent::__construct( 'qapress-list', '#QAPress#问题列表', array(
            'classname'   => 'widget_qapress_list',
            'description' => '问答插件问题列表',
        ) );
    }

    public function widget( $args, $instance ) {
        $title = $instance['title'];
        $orderby_id = empty( $instance['orderby'] ) ? 0 :  $instance['orderby'];
        $number = empty( $instance['number'] ) ? 10 : absint( $instance['number'] );

        $orderby = 'date';
        if($orderby_id==1){
            $orderby = 'comment_count';
        }else if($orderby_id==2){
            $orderby = 'meta_value_num';
        }else if($orderby_id==3){
            $orderby = 'rand';
        }

        $parg = array(
            'showposts' => $number,
            'orderby' => $orderby,
            'post_type' => 'qa_post'
        );
        if($orderby=='meta_value_num') $parg['meta_key'] = 'views';

        $posts = new WP_Query( $parg );

        echo $args['before_widget'];

        if ( $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        if ( $posts->have_posts() ) : ?>
            <ul>
                <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
                <li>
                    <a target="_blank" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>">
                        <?php the_title(); ?>
                    </a>
                </li>
                <?php endwhile; wp_reset_postdata();?>
            </ul>
        <?php
        else:
            echo '<p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;">暂无内容</p>';
        endif;

        echo $args['after_widget'];
    }

    function update( $new_instance, $instance ) {
        $instance['title'] = $new_instance['title'];
        $instance['number'] = $new_instance['number'];
        $instance['orderby'] = $new_instance['orderby'];
        return $instance;
    }

    function form( $instance ) {
        $title = isset($instance['title']) && $instance['title'] ? $instance['title'] :  '';
        $number = isset($instance['number']) && $instance['number'] ? $instance['number'] :  '10';
        $orderby = isset($instance['orderby']) && $instance['orderby'] ? $instance['orderby'] :  '0';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">标题：</label>
            <input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">显示数量：</label>
            <input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>">排序：</label>
            <select id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
                <option value="0" <?php selected( 0, $orderby ); ?>>发布时间</option>
                <option value="1" <?php selected( 1, $orderby ); ?>>回答数量</option>
                <option value="2" <?php selected( 2, $orderby ); ?>>浏览数</option>
                <option value="3" <?php selected( 3, $orderby ); ?>>随机排序</option>
            </select>
        </p>
    <?php
    }
}


// register widget
function QAPress_widget_new() {
    register_widget( 'QAPress_Widget_New' );
    register_widget( 'QAPress_Widget_List' );
}
add_action( 'widgets_init', 'QAPress_widget_new' );