<?php get_header(); ?>
    <div class="wrap container">
        <div class="main">
            <?php $is_fea_img = isset($options['fea_img']) && $options['fea_img'] && $options['fea_img'][0];
            if (isset($options['slider_img']) && $options['slider_img'] && $options['slider_img'][0]) { ?>
                <div class="slider-wrap clearfix">
                    <div class="main-slider wpcom-slider swiper-container<?php echo $is_fea_img ? ' pull-left' : ' slider-full'; ?>">
                        <ul class="swiper-wrapper">
                            <?php foreach ($options['slider_img'] as $k => $img) { ?>
                                <li class="swiper-slide">
                                    <?php if (isset($options['slider_url'][$k]) && $options['slider_url'][$k]) { ?>
                                        <a href="<?php echo esc_url($options['slider_url'][$k]); ?>" target="_blank">
                                            <img src="<?php echo esc_url($img); ?>"
                                                 alt="<?php echo esc_attr($options['slider_title'][$k]); ?>">
                                        </a>
                                        <?php if (isset($options['slider_title'][$k]) && $options['slider_title'][$k]) { ?>
                                            <h3 class="slide-title">
                                                <a href="<?php echo esc_url($options['slider_url'][$k]); ?>"
                                                   target="_blank"><?php echo $options['slider_title'][$k]; ?></a>
                                            </h3>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img src="<?php echo esc_url($img); ?>"
                                             alt="<?php echo esc_attr($options['slider_title'][$k]); ?>">
                                        <?php if (isset($options['slider_title'][$k]) && $options['slider_title'][$k]) { ?>
                                            <h3 class="slide-title">
                                                <?php echo $options['slider_title'][$k]; ?>
                                            </h3>
                                        <?php } ?>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                        <!-- Add Pagination -->
                        <div class="swiper-pagination"></div>
                        <!-- Add Navigation -->
                        <div class="swiper-button-prev swiper-button-white"></div>
                        <div class="swiper-button-next swiper-button-white"></div>
                    </div>

                    <?php if ($is_fea_img) { ?>
                        <ul class="feature-post pull-right">
                            <?php $i = 0;
                            foreach ($options['fea_img'] as $k => $img) {
                                if ($i < 3) { ?>
                                    <li>
                                        <?php if (isset($options['fea_url'][$k]) && $options['fea_url'][$k]) { ?>
                                            <a href="<?php echo esc_url($options['fea_url'][$k]); ?>" target="_blank">
                                                <?php echo wpcom_lazyimg($img, $options['fea_title'][$k]); ?>
                                            </a>
                                            <?php if (isset($options['fea_title'][$k]) && $options['fea_title'][$k]) { ?>
                                                <span><?php echo $options['fea_title'][$k]; ?></span>
                                            <?php } ?>
                                        <?php } else {
                                            echo wpcom_lazyimg($img, $options['fea_title'][$k]);
                                            if (isset($options['fea_title'][$k]) && $options['fea_title'][$k]) { ?>
                                                <span><?php echo $options['fea_title'][$k]; ?></span>
                                            <?php } ?>
                                        <?php } ?>
                                    </li>
                                <?php }
                                $i++;
                            } ?>
                        </ul>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php do_action('wpcom_echo_ad', 'ad_home_1'); ?>

            <?php
            if (isset($options['special_on']) && $options['special_on'] == '1'
                && isset($options['special_home_num']) && $options['special_home_num']) {
                $special = get_special_list($options['special_home_num']);
                /*
                 * 控制此处 $special 的结果
                 * 两种方案：
                 *  1. 在此处查询当前用户的授权专题，然和将special中的专题取交集
                 *  2. 处理查询$special的逻辑，在查询的过程中，加入筛选条件
                 * */
                // 使用第1种方案
                // 获取当前用户已授权的专题
                // $currUser = wp_get_current_user();

                // 1. 获取当前用户的levelids，根据levelids获取levelnames(post_name)
                // 2. 获取专题列表中，每个专题的扩展'special-access-level'字段的值，如果为null或者包含任意一个levelname，就展示


                // 获取当前用户的等级ID
                $userLevelIds = rua_get_user()->get_level_ids();
                $specialTmp = array();

                // 如果是管理员，不做权限判断
                if (!is_super_admin()) {
                    if ($userLevelIds == null) {
                        // 如果ids为null，只放开没有等级控制的专题
                        // 遍历$special,获取其meta值，如果其meta值中key为special-access-level的value为null的放入临时数据
                        foreach ($special as $item) {
                            $tmp = get_term_meta($item->term_id, 'special-access-level', true);
                            if ($tmp == null) {
                                array_push($specialTmp, $item);
                            }
                        }
                        // 将结果赋给专题数据
                        $special = $specialTmp;
                    } else {
                        // 拼接sql中的ids的字符串
                        $param_ids = '';
                        foreach ($userLevelIds as $id) {
                            $param_ids .= ($id . ",");
                        }
                        $param_ids = substr($param_ids, 0, strlen($param_ids) - 1);
                        // 如果ids为null，除了放开没有登记控制的专题，也放开拥有权限的专题
                        $rows = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts` WHERE `id` in (" . $param_ids . ")", ARRAY_A);
                        $userSpecialAccessLevelName = array();
                        foreach ($rows as $row) {
                            array_push($userSpecialAccessLevelName, $row['post_name']);
                        }

                        foreach ($special as $item) {
                            $tmp = get_term_meta($item->term_id, 'special-access-level', true);
                            if ($tmp == null) {
                                array_push($specialTmp, $item);
                            } else {
                                foreach ($userSpecialAccessLevelName as $name) {
                                    // 如果tmp中包含name，有权限
                                    if (strpos($tmp, $name) !== false) {
                                        array_push($specialTmp, $item);
                                    }
                                }
                            }
                        }
                        // 将结果赋给专题数据
                        $special = $specialTmp;
                    }
                }


                if ($special) { ?>
                    <div class="sec-panel topic-recommend">
                        <?php if (isset($options['special_home_title']) && $options['special_home_title']) { ?>
                            <div class="sec-panel-head">
                                <h2><span><?php echo $options['special_home_title']; ?></span>
                                    <small><?php echo $options['special_home_desc']; ?></small> <?php if (isset($options['special_home_url']) && $options['special_home_url']) { ?>
                                        <a href="<?php echo esc_url($options['special_home_url']); ?>" target="_blank"
                                           class="more"><?php $more_special = isset($options['more_special']) && $options['more_special'] ? $options['more_special'] : __('All Topics', 'wpcom');
                                        echo $more_special; ?></a><?php } ?></h2>
                            </div>
                        <?php } ?>
                        <!--专题的div-->
                        <div class="sec-panel-body">
                            <ul class="list topic-list">
                                <!--
                                    $special：专题的结果集
                                    在展示前，将 $special 的结果中没有授权的专题剔除掉
                                -->
                                <?php foreach ($special as $sp) {
                                    $thumb = get_term_meta($sp->term_id, 'wpcom_thumb', true);
                                    ?>
                                    <li class="topic">
                                        <a class="topic-wrap" href="<?php echo get_term_link($sp->term_id); ?>"
                                           target="_blank">
                                            <div class="cover-container">
                                                <?php echo wpcom_lazyimg($thumb, $sp->name); ?>
                                            </div>
                                            <span><?php echo $sp->name; ?></span>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php }
            } ?>
            <?php do_action('wpcom_echo_ad', 'ad_home_2'); ?>
            <?php
            global $is_sticky;
            $is_sticky = 1;
            $cats = isset($options['cats_id']) && $options['cats_id'] ? $options['cats_id'] : array();
            ?>
            <div class="sec-panel main-list">
                <div class="sec-panel-head">
                    <ul class="list tabs j-newslist">
                        <li class="tab active"><a data-id="0"
                                                  href="javascript:"><?php $latest = isset($options['latest_title']) && $options['latest_title'] ? $options['latest_title'] : __('Latest Posts', 'wpcom');
                                echo $latest; ?></a></li>
                        <?php if ($cats) {
                            foreach ($cats as $cat) { ?>
                                <li class="tab"><a data-id="<?php echo $cat; ?>"
                                                   href="javascript:"><?php echo get_cat_name($cat); ?></a></li>
                            <?php }
                        } ?>
                    </ul>
                </div>
                <ul class="post-loop post-loop-default tab-wrap clearfix active">
                    <?php
                    $per_page = get_option('posts_per_page');
                    $arg = array(
                        'posts_per_page' => $per_page,
                        'ignore_sticky_posts' => 0,
                        'post_type' => 'post',
                        'post_status' => array('publish'),
                        'category__not_in' => isset($options['newest_exclude']) ? $options['newest_exclude'] : array()
                    );
                    $posts = new WP_Query($arg);
                    if ($posts->have_posts()) {
                        while ($posts->have_posts()) {
                            $posts->the_post(); ?>
                            <?php get_template_part('templates/loop', 'default'); ?>
                        <?php }
                    }
                    wp_reset_postdata(); ?>
                    <?php if ($posts->max_num_pages > 1) { ?>
                        <li class="load-more-wrap">
                            <a class="load-more j-load-more"
                               href="javascript:"><?php _e('Load more posts', 'wpcom'); ?></a>
                        </li>
                    <?php } ?>
                </ul>
                <?php if ($cats) {
                    foreach ($cats as $cat) { ?>
                        <ul class="post-loop post-loop-default tab-wrap clearfix"></ul>
                    <?php }
                } ?>
            </div>

        </div>
        <aside class="sidebar">
            <?php get_sidebar(); ?>
        </aside>
    </div>

<?php
$partners = isset($options['pt_img']) && $options['pt_img'] ? $options['pt_img'] : array();
$link_cat = isset($options['link_cat']) && $options['link_cat'] ? $options['link_cat'] : '';
$bookmarks = get_bookmarks(array('limit' => -1, 'category' => $link_cat, 'category_name' => '', 'hide_invisible' => 1, 'show_updated' => 0));
if ($partners && $partners[0] || $bookmarks) {
    ?>
    <div class="container hidden-xs j-partner">
        <div class="sec-panel">
            <?php if ($partners && $partners[0]) {
                if (isset($options['partner_title']) && $options['partner_title']) {
                    ?>
                    <div class="sec-panel-head">
                        <h2><span><?php echo $options['partner_title']; ?></span>
                            <small><?php echo $options['partner_desc']; ?></small>
                            <a href="<?php echo esc_url($options['partner_more_url']); ?>" target="_blank"
                               class="more"><?php echo $options['partner_more_title']; ?></a></h2>
                    </div>
                <?php } ?>
                <div class="sec-panel-body">
                    <ul class="list list-partner">
                        <?php
                        $cols = isset($options['partner_img_cols']) && $options['partner_img_cols'] ? $options['partner_img_cols'] : 7;
                        $width = floor(10000 / $cols) / 100;
                        foreach ($partners as $x => $pt) {
                            $url = $options['pt_url'] && $options['pt_url'][$x] ? $options['pt_url'][$x] : '';
                            $alt = $options['pt_title'] && $options['pt_title'][$x] ? $options['pt_title'][$x] : '';
                            ?>
                            <li style="width:<?php echo $width; ?>%">
                                <?php if ($url){ ?><a target="_blank" title="<?php echo esc_attr($alt); ?>"
                                                      href="<?php echo esc_url($url); ?>"
                                                      rel="nofollow"><?php } ?><?php echo wpcom_lazyimg($pt, $alt); ?><?php if ($url){ ?></a><?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php }
            if ($bookmarks) {
                if (isset($options['link_title']) && $options['link_title']) {
                    ?>
                    <div class="sec-panel-head">
                        <h2><span><?php echo $options['link_title']; ?></span>
                            <small><?php echo $options['link_desc']; ?></small>
                            <a href="<?php echo esc_url($options['link_more_url']); ?>" target="_blank"
                               class="more"><?php echo $options['link_more_title']; ?></a></h2>
                    </div>
                <?php } ?>

                <div class="sec-panel-body">
                    <div class="list list-links">
                        <?php foreach ($bookmarks as $link) {
                            if ($link->link_visible == 'Y') { ?>
                                <a <?php if ($link->link_target){ ?>target="<?php echo $link->link_target; ?>"
                                   <?php } ?><?php if ($link->link_description){ ?>title="<?php echo esc_attr($link->link_description); ?>"
                                   <?php } ?>href="<?php echo $link->link_url ?>"<?php if ($link->link_rel) { ?> rel="<?php echo $link->link_rel; ?>"<?php } ?>><?php echo $link->link_name ?></a>
                            <?php }
                        } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } ?>
<?php get_footer(); ?>