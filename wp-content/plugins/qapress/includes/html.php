<?php
add_shortcode("QAPress", "QAPress_render");
function QAPress_render()
{
    global $wp_query, $current_cat;
    if (isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post') {
        return QAPress_single();
    } else {
        $page = isset($wp_query->query['qa_page']) && $wp_query->query['qa_page'] ? $wp_query->query['qa_page'] : 1;
        return QAPress_list($page);
    }
}

function QAPress_list($page = 1)
{
    global $wp_query, $wpcomqadb, $qa_options, $current_cat;
    if (!isset($qa_options)) $qa_options = get_option('qa_options');

    $per_page = isset($qa_options['question_per_page']) && $qa_options['question_per_page'] ? $qa_options['question_per_page'] : 20;

    $qa_cats = isset($qa_options['category']) && $qa_options['category'] ? $qa_options['category'] : array();

    $cat = isset($wp_query->query['qa_cat']) && $wp_query->query['qa_cat'] ? $wp_query->query['qa_cat'] : '';
    if (!$current_cat) $current_cat = $cat ? get_term_by('slug', $cat, 'qa_cat') : null;

    $list = $wpcomqadb->get_questions($per_page, $page, $current_cat ? $current_cat->term_id : 0);

    if ($list) {
        $users_id = array();
        foreach ($list as $p) {
            if (!in_array($p->user, $users_id)) $users_id[] = $p->user;
            if (!in_array($p->last_answer, $users_id)) $users_id[] = $p->last_answer;
        }

        $user_array = get_users(array('include' => $users_id));
        $users = array();
        foreach ($user_array as $u) {
            $users[$u->ID] = $u;
        }
    }

    $html = '<div class="q-content q-panel"><div class="q-header"><div class="q-header-tab"><a href="' . QAPress_category_url('') . '" class="topic-tab' . ($cat == '' ? ' current-tab' : '') . '">全部</a>';
    if ($qa_cats && $qa_cats[0]) {
        foreach ($qa_cats as $cid) {
            $c = get_term(trim($cid), 'qa_cat');
            if ($c) {
                $html .= '<a href="' . QAPress_category_url($c->slug) . '" class="topic-tab' . ($cat == $c->slug || $cat == urldecode($c->slug) ? ' current-tab' : '') . '">' . $c->name . '</a>';
            }
        }
    }
    $new_page_id = $qa_options['new_page'];
    $new_url = get_permalink($new_page_id);
    $html .= '</div><div class="q-mobile-ask"><a href="' . esc_url($new_url) . '"><img src="' . QAPress_URI . 'images/edit.png" alt="提问"> 提问</a></div>';
    $html .= '</div><div class="q-topic-wrap"><div class="q-topic-list">';
    $html .= '<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .q2-topic-item {
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
        padding: 15px 20px;
        width: 100%;
        overflow: hidden;
        line-height: initial;
    }

    .q2-topic-item > .item-left {
        float: left;
        width: 55px;
        position: relative;
    }

    .q2-topic-item > .item-right {
        float: right;
        width: calc(100% - 55px);
        padding-left: 20px;
    }

    .item-left > .reply-count2 {
        width: 50px;
        height: 50px;

        color: #017E66;
        background: rgba(1, 126, 102, 0.08);
        border: 1px solid rgba(1, 126, 102, 0.16);
        border-radius: 3px;

        text-align: center;
        font-size: 16px;
        padding-top: 2px;
    }

    .item-right > .reply-user {
        font-size: 13px;
        color: #999;
        margin-bottom: 4px;
    }

    .item-right > .topic-title-wrapper a {
        text-decoration: none;
        color: #666;
    }

    .item-right > .last-info {
        margin-top: 4px;
        font-size: 12px;
        color: #777;
    }

    #reply-name{
        text-indent: 0;
    }
</style>';

    if ($list) {
        // 这里循环输出每个帖子
        foreach ($list as $question) {

            $html .= '
            <div class="q2-topic-item">
            
               <!--回答及回答数小方框-->
                <div class="item-left">
                    <div class="reply-count2" title="回答" 
                    ' . isCountGreatThan0($question->comment_count) . ' 
                    >
                        <span class="count-of-replies" >' . $question->comment_count . '</span>
                        <p id="reply-name">回答</p>
                    </div>
                </div>

                <!--帖子情况，分三行-->
                <div class="item-right">
                    <!--1 操作人和最后操作时间-->
                    <div class="reply-user">
                        <!--最后操作人，以后在添加-->
                        <!--最后操作时间-->
                        <span class="last-active-time">' . QAPress_format_date(get_post_modified_time('U', false, $question->ID)) . '</span>
                    </div>
                    <!--2 标题-->
                    <div class="topic-title-wrapper">
                    ' . ' 
                        <a class="topic-title" href="' . get_permalink($question->ID) . '" title="' . esc_attr(get_the_title($question->ID)) . '" target="_blank">
                            ' . get_the_title($question->ID) . '
                        </a>
                    </div>
                    <!--3 分类标签，浏览量-->
                    <div class="last-info">
                    ' . ($question->menu_order == 1 ? '
                        <span class="put-top">置顶</span>
                    ' : '
                        <span class="topiclist-tab">' . QAPress_category($question) . '</span>
                    ') . '
                        <span class="count-of-visits"><i class="fa fa-eye"></i> ' . ($question->views ? $question->views : 0) . '浏览</span>
                    </div>                        
                </div>
            </div>';
        }
    } else {
        $html .= '<div class="q-topic-item"><p style="padding: 10px;margin: 0;text-align: center;color:#888;">暂无内容</p></div>';
    }
    $html .= '</div>' . QAPress_pagination($per_page, $page, $current_cat) . '</div></div>';
    return $html;
}

function isCountGreatThan0($countNum)
{
    return $countNum == 0 ? 'style="color: #AD3A37; background: rgba(173,58,55, 0.08); border: 1px solid rgba(173,58,55, 0.16);"' : null;
}

function QAPress_single()
{
    global $wpcomqadb, $wp_query, $qa_options;
    $post_id = isset($wp_query->query['p']) ? $wp_query->query['p'] : $wp_query->query['qa_id'];
    if (!$post_id) return;
    if (!isset($qa_options)) $qa_options = get_option('qa_options');

    $answers_per_page = isset($qa_options['answers_per_page']) && $qa_options['answers_per_page'] ? $qa_options['answers_per_page'] : 20;

    $question = get_post($post_id);

    if (!($question && isset($question->ID))) {
        exit();
    }

    $user = get_user_by('ID', $question->post_author);
    $author_name = $user->display_name ? $user->display_name : $user->user_nicename;
    if (class_exists('WPCOM_Member')) {
        $url = get_author_posts_url($user->ID);
        $author_name = '<a href="' . $url . '" target="_blank">' . $author_name . '</a>';
    }
    $answers_order = isset($qa_options['answers_order']) && $qa_options['answers_order'] == '1' ? 'DESC' : 'ASC';
    $answers = $wpcomqadb->get_answers($question->ID, $answers_per_page, 1, $answers_order);
    $cat = get_the_terms($question->ID, 'qa_cat');
    $cat = $cat[0];

    $html = '<div class="q-content q-single" data-id="' . $question->ID . '">
            <div class="q-header topic-header">
                ' . ($question->menu_order == 1 ? '<span class="put-top">置顶</span>' : '') . '
                <h1 class="q-title">' . get_the_title($question->ID) . '</h1>
                <div class="q-info">';
    if (current_user_can('manage_options')) {
        $edit_url = QAPress_edit_url($question->ID);
        $html .= '<div class="pull-right qa-manage">';
        if ($question->post_status == 'pending') $html .= '<a class="j-approve" href="javascript:;">审核通过</a>';
        $html .= '<a href="' . $edit_url . '">编辑</a>
            <a class="j-set-top" href="javascript:;">' . ($question->menu_order == 1 ? '取消置顶' : '置顶') . '</a>
            <a class="j-del" href="javascript:;">删除</a>
        </div>';
    }
    $html .= '<span class="q-author">' . $author_name . '</span>
                    <span class="q-author">发布于 ' . QAPress_format_date(get_post_time('U', false, $question->ID)) . '</span>
                    <span class="q-cat">分类：<a href="' . QAPress_category_url($cat->slug) . '">' . $cat->name . '</a></span>
                </div>
            </div>
            <div class="q-entry entry-content">' . wpautop(do_shortcode($question->post_content)) . '</div>

            <div class="q-answer" id="answer">
                <h3 class="as-title">' . $question->comment_count . '个回复</h3> <ul class="as-list">';
    if ($answers) {
        foreach ($answers as $answer) {
            $user = get_user_by('ID', $answer->user_id);
            $author_name = $answer->comment_author;
            $avatar = get_avatar($answer->user_id ? $answer->user_id : $answer->comment_author_email, '60', '', $author_name);
            if ($user) {
                $author_name = isset($user->display_name) ? $user->display_name : $user->user_nicename;
                if (class_exists('WPCOM_Member')) {
                    $url = get_author_posts_url($user->ID);
                    $author_name = '<a href="' . $url . '" target="_blank">' . $author_name . '</a>';
                    $avatar = '<a href="' . $url . '" target="_blank">' . get_avatar($answer->user_id, '60', '', $user->display_name) . '</a>';
                }
            }

            $html .= '<li id="as-' . $answer->comment_ID . '" class="as-item" data-aid="' . $answer->comment_ID . '">
                        <div class="as-avatar">' . $avatar . '</div>
                        <div class="as-main">
                            <div class="as-user">' . $author_name . '</div>
                            <div class="as-content entry-content">' . wpautop($answer->comment_content) . '</div>
                            <div class="as-action">
                                <span class="as-time">' . QAPress_format_date(strtotime($answer->comment_date)) . '</span>
                                <span class="as-reply-count"><a class="j-reply-list" href="javascript:;">' . $answer->comment_karma . '条评论</a></span>
                                <span class="as-reply"><a class="j-reply" href="javascript:;">我来评论</a></span>';
            if (current_user_can('manage_options')) $html .= '<span class="as-del"><a class="j-answer-del" href="javascript:;">删除</a></span>';
            $html .= '       </div>
                        </div>
                    </li>';
        }
    } else {
        $html .= '<li class="as-item-none" style="text-align: center;color: #999;padding-top: 10px;">暂无回复</li>';
    }

    $html .= '</ul>';

    if ($question->comment_count > $answers_per_page) {
        $html .= '<div class="q-load-wrap"><a class="q-load-more" href="javascript:;">加载更多评论</a></div>';
    }

    $current_user = wp_get_current_user();
    if ($current_user->ID) {
        $allow_img = isset($qa_options['answer_img']) && $qa_options['answer_img'] ? 1 : 0;
        ob_start();
        wp_editor('', 'editor-answer', QAPress_editor_settings(array('textarea_name' => 'answer', 'allow_img' => $allow_img)));
        $editor_contents = ob_get_clean();
        $answer_html = '<form id="as-form" class="as-form" action="" method="post" enctype="multipart/form-data">
                    <h3 class="as-form-title">我来回复</h3>
                    ' . $editor_contents . '
                    <input type="hidden" name="id" value="' . $question->ID . '">
                    <div class="as-submit clearfix">
                        <div class="pull-right"><input class="btn-submit" type="submit" value="提 交"></div>
                    </div>
                </form>';
    } else {
        $login_url = isset($qa_options['login_url']) && $qa_options['login_url'] ? $qa_options['login_url'] : wp_login_url();
        $register_url = isset($qa_options['register_url']) && $qa_options['register_url'] ? $qa_options['register_url'] : wp_registration_url();
        $answer_html = '<div class="as-login-notice">请 <a href="' . $login_url . '">登录</a> 或 <a href="' . $register_url . '">注册</a> 后回复</div>';
    }

    $html .= $answer_html . '</div></div>';
    return $html;
}

add_action('template_redirect', 'QAPress_pre_process_shortcode');
function QAPress_pre_process_shortcode()
{
    if (!is_singular('page')) return;
    global $post, $qa_options;
    if (!isset($qa_options)) $qa_options = get_option('qa_options');

    if (isset($qa_options['new_page']) && $qa_options['new_page'] == $post->ID && !is_user_logged_in()) {
        $login_url = isset($qa_options['login_url']) && $qa_options['login_url'] ? $qa_options['login_url'] : wp_login_url();
        wp_redirect($login_url);
        exit;
    }
}

add_shortcode("QAPress-new", "QAPress_new_question");
function QAPress_new_question()
{
    global $wpcomqadb, $qa_options, $pagenow;
    if ($pagenow == 'post.php') return false;
    if (!isset($qa_options)) $qa_options = get_option('qa_options');

    $current_user = wp_get_current_user();

    $category = '';
    $id = 0;
    $title = '';
    $content = '';

    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $is_allowed = 1;
    if ($type == 'edit') {
        $question = $id ? $wpcomqadb->get_question($id) : '';
        if ($question && ($question->post_author == $current_user->ID || $current_user->has_cap('edit_others_posts'))) { // 问题存在，并比对用户权限
            $title = $question->post_title;
            $category = get_the_terms($question->ID, 'qa_cat');
            $category = $category[0]->term_id;
            $content = $question->post_content;
        } else {
            // 无权限
            $is_allowed = 0;
        }
    }

    if ($is_allowed) {
        $allow_img = isset($qa_options['question_img']) && $qa_options['question_img'] ? 1 : 0;
        ob_start();
        wp_editor($content, 'editor-question', QAPress_editor_settings(array('textarea_name' => 'content', 'height' => 250, 'allow_img' => $allow_img)));
        $editor_contents = ob_get_clean();

        $qa_cats = QAPress_categorys();

        $html = '<div class="q-content">
            <form action="" method="post" id="question-form">';
        if (isset($id) && $id) {
            $html .= '<input type="hidden" name="id" value="' . $id . '">';
        }
        $html .= wp_nonce_field('QAPress_add_question', 'add_question_nonce', true, false);
        $html .= '<div class="q-header q-add-header clearfix">
                    <div class="q-add-title">
                        <div class="q-add-label">标题：</div>
                        <div class="q-add-input"><input type="text" name="title" placeholder="请输入标题" value="' . $title . '"></div>
                    </div>
                    <div class="q-add-cat">
                        <div class="q-add-label">分类：</div>
                        <div class="q-add-input">
                            <select name="category" id="category">
                                <option value="">请选择</option>';
        if ($qa_cats) {
            foreach ($qa_cats as $cat) {
                $html .= '<option value="' . $cat->term_id . '"' . ($category == $cat->term_id ? ' selected' : '') . '>' . $cat->name . '</option>';
            }
        }
        $html .= '</select></div></div>
                    <div class="q-add-btn"><input class="btn btn-post" type="submit" value="发布"></div>
                </div>
                <div class="q-add-main">' . $editor_contents . '</div>
            </form>
        </div>';
    } else {
        $html = '<div style="text-align:center;padding: 30px 0;font-sisze: 14px;color:#666;">您无权限访问此页面</div>';
    }
    return $html;
}

function QAPress_pagination($per_page = 20, $page = 1, $cat = null)
{
    global $wpcomqadb;
    $total_q = $wpcomqadb->get_questions_total($cat ? $cat->term_id : 0);
    $numpages = ceil($total_q / $per_page);
    $range = 9;

    if ($numpages > 1) {
        $cat_slug = $cat ? $cat->slug : '';

        $html = '<div class="q-pagination clearfix">';
        $prev = $page - 1;
        if ($prev > 0) {
            $html .= '<a class="prev" href="' . QAPress_category_url($cat_slug, $prev) . '">' . __('&laquo; Previous', 'wpcom') . '</a>';
        }

        if ($numpages > $range) {
            if ($page < $range) {
                for ($i = 1; $i <= ($range + 1); $i++) {
                    if ($i == $page) {
                        $html .= '<a class="current" href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                    } else {
                        $html .= '<a href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                    }
                }
            } elseif ($page >= ($numpages - ceil(($range / 2)))) {
                for ($i = $numpages - $range; $i <= $numpages; $i++) {
                    if ($i == $page) {
                        $html .= '<a class="current" href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                    } else {
                        $html .= '<a href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                    }
                }
            } elseif ($page >= $range && $page < ($numpages - ceil(($range / 2)))) {
                for ($i = ($page - ceil($range / 2)); $i <= ($page + ceil(($range / 2))); $i++) {
                    if ($i == $page) {
                        $html .= '<a class="current" href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                    } else {
                        $html .= '<a href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                    }
                }
            }
        } else {
            for ($i = 1; $i <= $numpages; $i++) {
                if ($i == $page) {
                    $html .= '<a class="current" href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                } else {
                    $html .= '<a href="' . QAPress_category_url($cat_slug, $i) . '">' . $i . "</a>";
                }
            }
        }

        $next = $page + 1;
        if ($next <= $numpages) {
            $html .= '<a class="next" href="' . QAPress_category_url($cat_slug, $next) . '">' . __('Next &raquo;', 'wpcom') . '</a>';
        }
        $html .= '</div>';
        return $html;
    }
}
