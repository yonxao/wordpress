<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'wpcom_login_form_items', 'wpcom_login_form_items' );
function wpcom_login_form_items( $items = array() ){
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Username', 'label', 'wpcom'),
            'icon' => FRAMEWORK_URI . '/assets/images/user.svg',
            'name' => 'user_login',
            'require' => true,
            'placeholder' =>  is_wpcom_enable_phone() ? __('Phone number / E-mail / Username', 'wpcom') : __('Username or email address', 'wpcom')
        ),
        20 => array(
            'type' => 'password',
            'label' => _x('Password', 'label', 'wpcom'),
            'icon' => 'lock',
            'name' => 'user_password',
            'require' => true,
            'placeholder' => _x('Password', 'placeholder', 'wpcom'),
        ),
        30 => array(
            'type' => wpcom_member_captcha_type()
        )
    );
    return $items;
}

add_filter( 'wpcom_register_form_items', 'wpcom_register_form_items' );
function wpcom_register_form_items( $items = array() ){
    if(is_wpcom_enable_phone()) {
        $items += apply_filters( 'wpcom_sms_code_items', array() );
        $items += array(
            40 => array(
                'type' => 'password',
                'label' => _x('Password', 'label', 'wpcom'),
                'icon' => 'lock',
                'name' => 'user_pass',
                'require' => true,
                'validate' => 'password',
                'placeholder' => _x('Password', 'placeholder', 'wpcom'),
            )
        );
    }else{
        $items += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Email address', 'label', 'wpcom'),
                'icon' => 'envelope',
                'name' => 'user_email',
                'require' => true,
                'validate' => 'email',
                'placeholder' => _x('Email address', 'placeholder', 'wpcom'),
            ),
            20 => array(
                'type' => 'password',
                'label' => _x('Password', 'label', 'wpcom'),
                'icon' => 'lock',
                'name' => 'user_pass',
                'require' => true,
                'validate' => 'password',
                'placeholder' => _x('Password', 'placeholder', 'wpcom'),
            ),
            30 => array(
                'type' => 'password',
                'label' => _x('Password', 'label', 'wpcom'),
                'icon' => 'lock',
                'name' => 'user_pass2',
                'require' => true,
                'validate' => 'password:user_pass',
                'placeholder' => _x('Confirm password', 'placeholder', 'wpcom'),
            ),
            40 => array(
                'type' => wpcom_member_captcha_type()
            ),
        );
    }
    return $items;
}

add_filter( 'wpcom_sms_code_items', 'wpcom_sms_code_items' );
function wpcom_sms_code_items($items){
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Phone number', 'label', 'wpcom'),
            'icon' => FRAMEWORK_URI . '/assets/images/phone.svg',
            'name' => 'user_phone',
            'require' => true,
            'validate' => 'phone',
            'placeholder' => _x('Phone number', 'placeholder', 'wpcom'),
        ),
        20 => array(
            'type' => wpcom_member_captcha_type()
        ),
        30 => array(
            'type' => 'smsCode',
            'label' => _x('验证码', 'label', 'wpcom'),
            'name' => 'sms_code',
            'icon' => FRAMEWORK_URI . '/assets/images/yzm.svg',
            'validate' => 'sms_code:user_phone',
            'target' => 'user_phone',
            'require' => true,
            'placeholder' => _x('请输入验证码', 'placeholder', 'wpcom')
        )
    );
    return $items;
}

add_filter( 'wpcom_email_code_items', 'wpcom_email_code_items' );
function wpcom_email_code_items($items){
    global $options;
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Email address', 'label', 'wpcom'),
            'icon' => 'envelope',
            'name' => 'user_email',
            'require' => true,
            'validate' => 'email',
            'placeholder' => _x('Email address', 'placeholder', 'wpcom'),
        ),
        20 => array(
            'type' => wpcom_member_captcha_type()
        ),
        30 => array(
            'type' => 'smsCode',
            'label' => _x('验证码', 'label', 'wpcom'),
            'name' => 'sms_code',
            'icon' => FRAMEWORK_URI . '/assets/images/yzm.svg',
            'validate' => 'sms_code:user_email',
            'target' => 'user_email',
            'require' => true,
            'placeholder' => _x('请输入验证码', 'placeholder', 'wpcom')
        )
    );
    return $items;
}

// 插入默认的配置数据
add_filter( 'wpcom_account_tabs', 'wpcom_account_default_tabs' );
function wpcom_account_default_tabs( $tabs = array() ){
    global $options;
    $tabs += array(
        10 => array(
            'slug' => 'general',
            'title' => __('General', 'wpcom'),
            'icon' => 'user'
        ),
        20 => array(
            'slug' => 'password',
            'title' => __('Password', 'wpcom'),
            'icon' => 'lock'
        ),
        30 => array(
            'slug' => 'logout',
            'title' => __('Logout', 'wpcom'),
            'icon' => 'sign-out'
        )
    );
    if(!is_wpcom_enable_phone() && !(isset($options['social_login_on']) && $options['social_login_on']=='1')){
        $tabs[98989] = array(
            'slug' => 'bind',
            'title' => __('帐号绑定', 'wpcom'),
            'icon' => 'shield',
            'parent' => 'general'
        );
    }else{
        $tabs[11] = array(
            'slug' => 'bind',
            'title' => __('帐号绑定', 'wpcom'),
            'icon' => 'shield'
        );
    }
    return $tabs;
}

add_filter( 'wpcom_account_tabs_general_metas', 'wpcom_account_tabs_general_metas' );
function wpcom_account_tabs_general_metas( $metas ){
    $user = wp_get_current_user();
    if( !$user->ID ) return $metas;

    if(is_wpcom_enable_phone()) {
        $phone = $user->mobile_phone;
        if($phone){
            $url = add_query_arg(array('type' => 'phone', 'action' => 'change'), wpcom_subpage_url('bind'));
            $phone .= '<a href="'.$url.'">修改</a>';
        }else{
            $url = add_query_arg(array('type' => 'phone', 'action' => 'bind'), wpcom_subpage_url('bind'));
            $phone = __('未绑定', 'wpcom') . '<a href="'.$url.'">'.__('绑定手机', 'wpcom').'</a>';
        }

        $metas += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Phone number', 'label', 'wpcom'),
                'name' => 'mobile_phone',
                'value' => $phone,
                'disabled' => true
            )
        );
    }
    $email = $user->user_email;
    if($email && !preg_match('/@email\.empty$/i', $email) && !preg_match('/@weixin\.qq$/i', $email)){
        $url = add_query_arg(array('type' => 'email', 'action' => 'change'), wpcom_subpage_url('bind'));
        $email .= '<a href="'.$url.'">修改</a>';
    }else{
        $url = add_query_arg(array('type' => 'email', 'action' => 'bind'), wpcom_subpage_url('bind'));
        $email = __('未绑定', 'wpcom') . '<a href="'.$url.'">'.__('绑定邮箱', 'wpcom').'</a>';
    }

    $metas += array(
        20 => array(
            'type' => 'text',
            'label' => _x('Email address', 'label', 'wpcom'),
            'name' => 'user_email',
            'maxlength' => 64,
            'require' => true,
            'validate' => 'email',
            'value' => $email,
            'disabled' => true
        ),
        30 => array(
            'type' => 'text',
            'label' => __('Nickname', 'wpcom'),
            'name' => 'display_name',
            'maxlength' => 20,
            'require' => true,
            'value' => $user->display_name
        ),
        40 => array(
            'type' => 'textarea',
            'label' => __('Description', 'wpcom'),
            'maxlength' => 200,
            'name' => 'description',
            'desc' => __('Optional, description can not exceed 200 characters', 'wpcom'),
            'value' => $user->description
        )
    );

    return $metas;
}

add_filter( 'wpcom_account_tabs_bind_metas', 'wpcom_account_tabs_bind_metas' );
function wpcom_account_tabs_bind_metas( $metas ){
    global $options;
    $user = wp_get_current_user();
    if( !$user->ID ) return $metas;

    if(is_wpcom_enable_phone()) {
        $phone = $user->mobile_phone;
        if($phone){
            $url = add_query_arg(array('type' => 'phone', 'action' => 'change'), wpcom_subpage_url('bind'));
            $phone .= '<a href="'.$url.'">修改</a>';
        }else{
            $url = add_query_arg(array('type' => 'phone', 'action' => 'bind'), wpcom_subpage_url('bind'));
            $phone = __('未绑定', 'wpcom') . '<a href="'.$url.'">'.__('绑定手机', 'wpcom').'</a>';
        }
        $metas += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Phone number', 'label', 'wpcom'),
                'name' => 'mobile_phone',
                'value' => $phone,
                'disabled' => true
            )
        );
    }
    $email = $user->user_email;
    if($email && !preg_match('/@email\.empty$/i', $email) && !preg_match('/@weixin\.qq$/i', $email)){
        $url = add_query_arg(array('type' => 'email', 'action' => 'change'), wpcom_subpage_url('bind'));
        $email .= '<a href="'.$url.'">修改</a>';
    }else{
        $url = add_query_arg(array('type' => 'email', 'action' => 'bind'), wpcom_subpage_url('bind'));
        $email = __('未绑定', 'wpcom') . '<a href="'.$url.'">'.__('绑定邮箱', 'wpcom').'</a>';
    }
    $metas += array(
        20 => array(
            'type' => 'text',
            'label' => _x('Email address', 'label', 'wpcom'),
            'name' => 'user_email',
            'maxlength' => 64,
            'require' => true,
            'validate' => 'email',
            'value' => $email,
            'disabled' => true
        )
    );
    if(isset($options['social_login_on']) && $options['social_login_on']=='1'){
        $key = 20;
        $socials = apply_filters( 'wpcom_socials', array() );
        ksort($socials);
        if( $socials ){
            foreach ( $socials as $social ){
                if( $social['id'] && $social['key'] ) {
                    $key += 10;
                    $url = add_query_arg(array('from' => 'bind'), wpcom_social_login_url($social['name']));
                    $value = __('未绑定', 'wpcom') . '<a class="j-social-bind '.$social['name'].'" href="'.$url.'">'.__('绑定帐号', 'wpcom').'</a>';
                    $social_name = $social['name'];
                    $social['name'] = $social['name'] === 'wechat2' ? 'wechat' : $social['name'];
                    $openid = get_user_option('social_type_'.$social['name'], $user->ID);
                    if($openid){
                        $value = __('已绑定', 'wpcom');
                        $name = get_user_option('social_type_'.$social['name'].'_name', $user->ID);
                        $value = $name ? $name : $value;
                        $value = $value . '<a class="j-social-unbind" href="javascript:;" data-name="'.$social_name.'">'.__('解除绑定', 'wpcom').'</a>';
                    }
                    $metas += array(
                        $key => array(
                            'type' => 'text',
                            'label' => str_replace('登录', '帐号', $social['title']),
                            'name' =>  $social['name'],
                            'value' => $value,
                            'disabled' => true
                        )
                    );
                }
            }
        }
    }
    return $metas;
}

add_filter( 'wpcom_account_tabs_password_metas', 'wpcom_account_tabs_password_metas' );
function wpcom_account_tabs_password_metas( $metas ){
    $metas += array(
        10 => array(
            'type' => 'password',
            'label' => _x('Old password', 'label', 'wpcom'),
            'name' => 'old-password',
            'require' => true,
            'value' => '',
            'placeholder' => _x('Please enter your old password', 'placeholder', 'wpcom')
        ),
        20 => array(
            'type' => 'password',
            'label' => _x('New password', 'label', 'wpcom'),
            'name' => 'password',
            'require' => true,
            'validate' => 'password',
            'maxlength' => 32,
            'minlength' => 6,
            'desc' => __('Password must be 6-32 characters', 'wpcom'),
            'value' => '',
            'placeholder' => _x('Please enter your new password', 'placeholder', 'wpcom')
        ),
        30 => array(
            'type' => 'password',
            'label' => _x('New password', 'label2', 'wpcom'),
            'name' => 'password2',
            'require' => true,
            'validate' => 'password:password',
            'value' => '',
            'placeholder' => _x('Please confirm your new password', 'placeholder', 'wpcom')
        )
    );

    return $metas;
}

add_filter( 'wpcom_lostpassword_form_items', 'wpcom_lostpassword_form_items' );
function wpcom_lostpassword_form_items( $items = array() ){
    global $options;
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Username', 'label', 'wpcom'),
            'icon' => 'user',
            'name' => 'user_login',
            'require' => true,
            'placeholder' =>  is_wpcom_enable_phone() ? __('Phone number / E-mail / Username', 'wpcom') : __('Username or email address', 'wpcom')
        ),
        30 => array(
            'type' => wpcom_member_captcha_type()
        )
    );
    return $items;
}

add_filter( 'wpcom_resetpassword_form_items', 'wpcom_resetpassword_form_items' );
function wpcom_resetpassword_form_items( $items = array() ){
    global $options;
    $items += array(
        10 => array(
            'type' => 'password',
            'label' => _x('New password', 'label', 'wpcom'),
            'name' => 'password',
            'icon' => 'lock',
            'require' => true,
            'validate' => 'password',
            'maxlength' => 32,
            'minlength' => 6,
            'desc' => __('Password must be 6-32 characters', 'wpcom'),
            'value' => '',
            'placeholder' => _x('Please enter your new password', 'placeholder', 'wpcom')
        ),
        20 => array(
            'type' => 'password',
            'label' => _x('New password', 'label2', 'wpcom'),
            'name' => 'password2',
            'icon' => 'lock',
            'require' => true,
            'validate' => 'password:password',
            'value' => '',
            'placeholder' => _x('Please confirm your new password', 'placeholder', 'wpcom')
        ),
        30 => array(
            'type' => wpcom_member_captcha_type()
        )
    );
    return $items;
}

add_filter( 'wpcom_member_errors', 'wpcom_member_errors' );
function wpcom_member_errors( $errors ){
    $captcha = wpcom_member_captcha_type();
    $errors += array(
        'require' => __( ' is required', 'wpcom' ),
        'email' => __( 'This is not a valid email', 'wpcom' ),
        'pls_enter' => __( 'Please enter your ', 'wpcom' ),
        'password' => __( 'Your password must be 6-32 characters', 'wpcom' ),
        'passcheck' => __( 'Your passwords do not match', 'wpcom' ),
        'phone' => __( 'Please enter a valid phone number', 'wpcom' ),
        'sms_code' => __( 'Your verification code error', 'wpcom' ),
        'captcha_verify' => $captcha == 'noCaptcha' ? __( 'Please slide to verify', 'wpcom' ) : __( 'Please click to verify', 'wpcom' ),
        'captcha_fail' => $captcha == 'noCaptcha' ? __( 'Slide verify failed, please try again', 'wpcom' ) : __( 'Click verify failed, please try again', 'wpcom' ),
        'nonce' => __( 'The nonce check failed', 'wpcom' ),
        'req_error' => __( 'Request Error!', 'wpcom' )
    );
    return $errors;
}

// 插入默认的配置数据
add_filter( 'wpcom_profile_tabs', 'wpcom_profile_default_tabs' );
function wpcom_profile_default_tabs( $tabs = array() ){
    global $options;
    $tabs[10] = array(
        'slug' => 'posts',
        'title' => __( 'Posts', 'wpcom' )
    );
    if ( isset($options['comments_open']) && $options['comments_open']=='1' ) {
        $tabs[20] = array(
            'slug' => 'comments',
            'title' => __( 'Comments', 'wpcom' )
        );
    }
    return $tabs;
}

add_filter( 'wpcom_socials', 'wpcom_socials' );
function wpcom_socials( $social ){
    global $options;
    $social += array(
        10 => array(
            'name' => 'qq',
            'title' => 'QQ登录',
            'icon' => 'qq',
            'id' => isset($options['sl_qq_id']) ? $options['sl_qq_id'] : '',
            'key' => isset($options['sl_qq_key']) ? $options['sl_qq_key'] : '',
        ),
        20 => array(
            'name' => 'weibo',
            'title' => '微博登录',
            'icon' => 'weibo',
            'id' => isset($options['sl_weibo_id']) ? $options['sl_weibo_id'] : '',
            'key' => isset($options['sl_weibo_key']) ? $options['sl_weibo_key'] : '',
        )

    );

    if( wp_is_mobile() || !(isset($options['sl_wechat_key']) && $options['sl_wechat_key'] && $options['sl_wechat_id']) ) {
        $social += array(
            30 => array(
                'name' => 'wechat2',
                'title' => '微信登录',
                'icon' => 'wechat',
                'id' => isset($options['sl_wechat2_id']) ? $options['sl_wechat2_id'] : '',
                'key' => isset($options['sl_wechat2_key']) ? $options['sl_wechat2_key'] : '',
            )
        );
    } else {
        $social += array(
            30 => array(
                'name' => 'wechat',
                'title' => '微信登录',
                'icon' => 'wechat',
                'id' => isset($options['sl_wechat_id']) ? $options['sl_wechat_id'] : '',
                'key' => isset($options['sl_wechat_key']) ? $options['sl_wechat_key'] : '',
            )
        );
    }
    return $social;
}

add_filter( 'wpcom_approve_resend_form_items', 'wpcom_approve_resend_form_items' );
function wpcom_approve_resend_form_items( $items = array() ){
    global $options;
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Username', 'label', 'wpcom'),
            'icon' => 'user',
            'name' => 'user_login',
            'require' => true,
            'placeholder' => __('Username or email address', 'wpcom'),
        ),
        20 => array(
            'type' => wpcom_member_captcha_type()
        ),
    );
    return $items;
}

if( ! function_exists('is_wpcom_member_page') ){
    function is_wpcom_member_page( $page = 'account' ){
        global $options;
        if ( isset($options['member_page_' . $page]) && $options['member_page_' . $page] && is_page($options['member_page_' . $page]) ) {
            return true;
        }
        return false;
    }
}

function wpcom_get_cover_url( $user_id ){
    $cover_img = FRAMEWORK_URI . '/assets/images/lazy.png';
    if ( $user_id && $url = get_user_meta( $user_id, 'wpcom_cover', 1) ) {
        if(preg_match('/^(http|https|\/\/)/i', $url)){
            $cover_img = $url;
        }else{
            $uploads = wp_upload_dir();
            $cover_img = $uploads['baseurl'] . $url;
        }
    } else {
        global $options;
        if( isset($options['member_cover']) && $options['member_cover'] )
            $cover_img = esc_url($options['member_cover']);
    }
    $cover_img = apply_filters( 'wpcom_member_user_cover', $cover_img, $user_id );

    $cover_img = preg_replace('/^(http|https):/i', '', $cover_img);
    return $cover_img;
}

function wpcom_get_user_group($user_id){
    if($user_id) {
        $group = wp_get_object_terms($user_id, 'user-groups');
        if (!is_wp_error($group) && isset($group[0])) return $group[0];
    }
}

function wpcom_send_active_email( $user_id ){
    $user = get_user_by( 'ID', $user_id );
    if(!$user->ID) return false;

    $key = get_password_reset_key( $user );
    $url = add_query_arg( array(
        'approve' => 'pending',
        'key' => $key,
        'login' => rawurlencode( $user->user_login )
    ), wp_registration_url() );

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $message = '<p>' . sprintf( __( 'Hi, %s!', 'wpcom' ), $user->display_name ) . '</p>';
    $message .= '<p>' . sprintf( __( 'Welcome to %s. To activate your account and verify your email address, please click the following link:', 'wpcom' ), $site_name ) . '</p>';
    $message .= '<p><a href="'.$url.'">'.$url.'</a></p><p></p>';
    $message .= '<p>' . __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "</p>";

    $title = sprintf( __( '[%s] Please verify your email address', 'wpcom' ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $user->user_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', 'wpcom');

    return true;
}

function wpcom_send_active_to_admin( $user_id ){
    $user = get_user_by( 'ID', $user_id );
    if(!$user->ID) return __( 'The user does not exist', 'wpcom' );

    $admin_email = get_option('admin_email');

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $message = '<p>' . sprintf( __( '%s has just created an account on %s!', 'wpcom' ), $user->display_name, $site_name ) . '</p>';
    $message .= '<p>' .sprintf( __( 'Username: %s', 'wpcom' ), $user->user_login ) . '</p>';
    $message .= '<p>' .sprintf( __( 'E-Mail: %s', 'wpcom' ), $user->user_email ) . '</p><p></p>';

    $message .= '<p>' . __( 'If you want to approve the new user, please go to wp-admin page.', 'wpcom' ) . '</p>';

    $title = sprintf( __( '[%s] New user account', 'wpcom' ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $admin_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', 'wpcom');

    return true;
}

function wpcom_send_actived_email( $user_id ){
    $user = get_user_by( 'ID', $user_id );
    if(!$user->ID) return false;

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $login_url = wpcom_login_url();

    $message = '<p>' . sprintf( __( 'Hi, %s!', 'wpcom' ), $user->display_name ) . '</p>';
    $message .= '<p>' . sprintf( __( 'Congratulations, your account has been activated successfully, you can now login: <a href="%s">%s</a>', 'wpcom' ), $login_url, $login_url ) . '</p>';

    $title = sprintf( __( '[%s] Welcome to join us', 'wpcom' ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $user->user_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', 'wpcom');

    return true;
}

function wpcom_send_email_code( $email ){
    $user = wp_get_current_user();
    if(!$user->ID) return false;

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $code = wpcom_generate_sms_code(sanitize_user($email, true));
    $message = '<p>' . sprintf( __( 'Hi, %s!', 'wpcom' ), $user->display_name ) . '</p>';
    $message .= '<p>' . sprintf( __( 'Your verification code is <b style="color:red;">%s</b>, please enter in 10 minutes.', 'wpcom' ), $code ) . '</p>';
    $message .= '<p></p>';
    $message .= '<p>' . __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "</p>";

    $title = sprintf( __( '[%s] Your verification code', 'wpcom' ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', 'wpcom');

    return true;
}

add_action('wp_ajax_wpcom_is_login', 'wpcom_is_login');
add_action('wp_ajax_nopriv_wpcom_is_login', 'wpcom_is_login');
// 登录状态
function wpcom_is_login(){
    $res = array();
    $current_user = wp_get_current_user();
    if($current_user->ID){
        global $options;
        $res['result'] = 0;
        $res['avatar'] = get_avatar( $current_user->ID, 60 );
        $res['url'] = get_author_posts_url( $current_user->ID );
        if( function_exists('wpcom_account_url') ) $res['account'] = wpcom_account_url();
        $res['display_name'] = $current_user->display_name;

        $res['menus'] = array();

        $show_profile = apply_filters( 'wpcom_member_show_profile' , true );
        if($show_profile) {
            $res['menus'][] = array(
                'url' => $res['url'],
                'title' => __('Profile', 'wpcom')
            );
        }

        if(isset($options['profile_menu_url']) && isset($options['profile_menu_title']) && $options['profile_menu_url']){
            $i=1;
            foreach($options['profile_menu_url'] as $menu){
                if($menu && $options['profile_menu_title'][$i-1]) {
                    $res['menus'][] = array(
                        'url' => esc_url($menu),
                        'title' => $options['profile_menu_title'][$i-1]
                    );
                }
                $i++;
            }
        }
        $res['menus'][] = array(
            'url' => isset( $res['account'] ) ? $res['account'] : $res['url'],
            'title' => __('Account', 'wpcom')
        );
        $res['menus'][] = array(
            'url' => wp_logout_url(),
            'title' => __( 'Logout', 'wpcom' )
        );
    }else{
        $res['result'] = -1;
    }

    if ( function_exists('is_woocommerce') ) {
        ob_start();

        woocommerce_mini_cart();

        $mini_cart = ob_get_clean();

        $data = array(
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
                    'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
                )
            ),
            'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ? md5( json_encode( WC()->cart->get_cart_for_session() ) ) : '', WC()->cart->get_cart_for_session() ),
        );

        $res['wc'] = $data;
    }
    echo wp_json_encode($res);
    die();
}

function is_wpcom_enable_phone(){
    global $options;
    return isset($options['enable_phone']) && $options['enable_phone'];
}

function wpcom_check_sms_code($phone, $val){
    // 检查session、验证码值
    $key = 'code_'.$phone;
    $code = WPCOM_Session::get($key);
    if($code && $code == $val ){
        return true;
    }
    return false;
}

function wpcom_generate_sms_code($phone){
    $code = '' . rand(0,9) . '' . rand(0,9) . '' . rand(0,9) . '' . rand(100,999);
    $key = 'code_'.$phone;
    WPCOM_Session::set($key, $code, 600);
    return $code;
}

function wpcom_generate_unique_username( $username ) {
    $username = sanitize_user( $username, true );
    static $i;
    if ( null === $i ) {
        $i = 1;
    } else {
        $i ++;
    }
    if ( ! username_exists( $username ) ) {
        return $username;
    }
    $new_username = sprintf( '%s%s', $username, $i );
    if ( ! username_exists( $new_username ) ) {
        return $new_username;
    } else {
        return call_user_func( __FUNCTION__, $username );
    }
}

function wpcom_mobile_phone_exists($phone){
    $args = array(
        'meta_key'     => 'mobile_phone',
        'meta_value'   => $phone,
    );
    $users = get_users($args);
    return isset($users[0]) && $users[0]->ID ? $users[0]->ID : false;
}

function wpcom_aliyun_sdk( $csessionid, $token, $sig, $scene ){
    include_once FRAMEWORK_PATH . '/member/aliyun-php-sdk/load.php';
    return AfsCheckRequest($csessionid, $token, $sig, $scene);
}

function wpcom_qcloud_sms_code( $phone ){
    include_once FRAMEWORK_PATH . '/member/qcloudsms/index.php';
    if($phone){
        $code = wpcom_generate_sms_code($phone);
        $params = array($code, 10);
        return wpcom_qcloud_sms_sender($phone, $params);
    }
}

function wpcom_member_captcha_type(){
    global $options;
    $type = '';
    if(isset($options['member_captcha']) && $options['member_captcha']!==''){
        switch ($options['member_captcha']){
            case '0': // 防水墙
                $type = 'TCaptcha';
                break;
            case '1': // 阿里云
            default:
                $type = 'noCaptcha';
                break;
        }
    }
    return $type;
}