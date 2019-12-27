<?php
// TEMPLATE NAME: 快讯页面
global $options;
global $options, $post;
$sidebar = get_post_meta( $post->ID, 'wpcom_sidebar', true );
$sidebar = !(!$sidebar && $sidebar!=='');
$class = $sidebar ? 'main' : 'main main-full';
get_header();?>
    <div class="wrap container">
        <div class="<?php echo esc_attr($class);?>">
            <?php if( isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) wpcom_breadcrumb('breadcrumb entry-breadcrumb'); ?>
            <div class="sec-panel sec-panel-kx">
                <?php while( have_posts() ) : the_post();?>
                    <div class="kx-head">
                        <h1 class="kx-title"><?php the_title();?></h1>
                    </div>
                <?php endwhile; ?>
                <?php
                $per_page = get_option('posts_per_page');
                $arg = array(
                    'posts_per_page' => $per_page,
                    'post_status' => array( 'publish' ),
                    'post_type' => 'kuaixun'
                );
                $posts = get_posts($arg);
                $cur_day = '';
                global $post;
                if( $posts ) { ?>
                    <div class="kx-list">
                        <?php  foreach ( $posts as $post ) { setup_postdata( $post );
                            if($cur_day != $date = get_the_date(get_option('date_format'))){
                                $pre_day = '';
                                $week = date_i18n(get_the_date('l'));
                                if(date(get_option('date_format'), current_time('timestamp')) == $date) {
                                    $pre_day = '今天 • ';
                                }else if(date(get_option('date_format'), current_time('timestamp')-86400) == $date){
                                    $pre_day = '昨天 • ';
                                }else if(date(get_option('date_format'), current_time('timestamp')-86400*2) == $date){
                                    $pre_day = '前天 • ';
                                }
                                echo '<div class="kx-date">'. $pre_day .$date . ' • ' . $week.'</div>';
                                if($cur_day=='') echo '<div class="kx-new"></div>';
                                $cur_day = $date;
                            } ?>
                            <div class="kx-item" data-id="<?php the_ID();?>">
                                <span class="kx-time"><?php the_time('H:i');?></span>
                                <div class="kx-content">
                                    <h2><?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                            <a href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a>
                                        <?php } else{ the_title(); } ?></h2>
                                    <?php the_excerpt();?>
                                    <?php if(get_the_post_thumbnail()){ ?>
                                        <?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                            <a class="kx-img" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank"><?php the_post_thumbnail('full'); ?></a>
                                        <?php }else{ ?>
                                            <div class="kx-img"><?php the_post_thumbnail('full'); ?></div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                                <div class="kx-meta clearfix" data-url="<?php echo urlencode(get_permalink());?>">
                                    <span class="j-mobile-share" data-id="<?php the_ID();?>">
                                        <i class="fa fa-share-alt"></i> <?php _e('Generate poster', 'wpcom');?>
                                    </span>
                                    <span class="hidden-xs"><?php _e('Share to: ', 'wpcom');?></span>
                                    <span class="share-icon wechat hidden-xs">
                                            <i class="fa fa-wechat"></i>
                                            <span class="wechat-img">
                                                <span class="j-qrcode" data-text="<?php the_permalink();?>"></span>
                                            </span>
                                        </span>
                                    <span class="share-icon weibo hidden-xs" href="javascript:;"><i class="fa fa-weibo"></i></span>
                                    <span class="share-icon qq hidden-xs" href="javascript:;"><i class="fa fa-qq"></i></span>
                                    <span class="share-icon copy hidden-xs"><i class="fa fa-file-text"></i></span>
                                </div>

                            </div>
                        <?php } ?>
                        <?php if(count($posts)==$per_page){ ?>
                            <div class="load-more-wrap">
                                <a class="load-more j-load-kx" href="javascript:;"><?php _e('Load more topics', 'wpcom');?></a>
                            </div>
                        <?php } ?>
                    </div>
                <?php } wp_reset_postdata(); ?>
            </div>
        </div>
        <?php if( $sidebar ){ ?>
            <aside class="sidebar">
                <?php get_sidebar();?>
            </aside>
        <?php } ?>
    </div>
<?php get_footer();?>