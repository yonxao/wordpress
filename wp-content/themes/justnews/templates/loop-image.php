<?php
global $is_sticky, $options, $post;
$video = get_post_meta( $post->ID, 'wpcom_video', true );
?>
<li class="item<?php echo $is_sticky&&is_sticky()?' item-sticky':'';?>">
    <div class="item-img">
        <a class="item-thumb<?php echo $video?' item-video':'';?>" href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>"<?php echo wpcom_post_target();?>>
            <?php the_post_thumbnail();?>
        </a>
        <?php
        $category = get_the_category();
        $cat = $category?$category[0]:'';
        if($cat){
        ?>
        <a class="item-category" href="<?php echo get_category_link($cat->cat_ID);?>" target="_blank"><?php echo $cat->name;?></a><?php } ?>
    </div>
    <h2 class="item-title">
        <a href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>"<?php echo wpcom_post_target();?>>
            <?php if($is_sticky&&is_sticky()){ ?><span class="sticky-post">置顶</span><?php } ?> <?php the_title();?>
        </a>
    </h2>
    <div class="item-meta">
        <span class="item-meta-left"><?php echo format_date(get_post_time( 'U', false, $post ));?></span>
        <span class="item-meta-right">
            <?php
            $post_metas = isset($options['post_metas']) && is_array($options['post_metas']) ? $options['post_metas'] : array();
            foreach ( $post_metas as $meta ) echo wpcom_post_metas($meta);
            ?>
        </span>
    </div>
</li>