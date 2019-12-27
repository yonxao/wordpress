<?php
defined( 'ABSPATH' ) || exit;

class social_login {
    public function __construct() {
        global $options;
        if( isset($options['social_login_on']) && $options['social_login_on']=='1' ) {
            $this->type = '';
            $this->options = $options;

            $socials = apply_filters( 'wpcom_socials', array() );
            ksort($socials);

            $this->social = array();
            foreach ( $socials as $social ){
                if( $social['id'] && $social['key'] ) {
                    $social['id'] = trim($social['id']);
                    $social['key'] = trim($social['key']);
                    $this->social[$social['name']] = $social;
                }
            }

            if( isset($this->social['wechat2']) && !isset($this->social['wechat'])){
                $this->social['wechat'] = $this->social['wechat2'];
            }

            if($this->social) {
                add_action( 'init', array($this, 'init'), 5 );
                add_action( 'body_class', array($this, 'body_class') );
                add_action( 'wp_footer', array($this, 'unset_session'));

                add_action('wp_ajax_wpcom_sl_login', array($this, 'login_to_bind'));
                add_action('wp_ajax_nopriv_wpcom_sl_login', array($this, 'login_to_bind'));

                add_action('wp_ajax_wpcom_sl_create', array($this, 'create'));
                add_action('wp_ajax_nopriv_wpcom_sl_create', array($this, 'create'));

                add_action('wp_ajax_wpcom_wechat2_login_check', array($this, 'wechat2_login_check'));
                add_action('wp_ajax_nopriv_wpcom_wechat2_login_check', array($this, 'wechat2_login_check'));
            }

            add_shortcode("wpcom-social-login", array($this, 'wpcom_social_login'));
        }
    }

    function init(){
        if ( isset($_GET['type']) && isset($_GET['action']) ) {
            global $options;
            $page_id = isset($options['social_login_page']) ? $options['social_login_page'] : '';
            $this->page = $page_id ? untrailingslashit(get_permalink($page_id)) : '';

            $this->type = $_GET['type'];
            if(!in_array($this->type, array_keys($this->social)) || !isset($_GET['action'])){
                return false;
            }

            $args = array( 'type'=>$this->type, 'action'=>'callback' );
            if(isset($_GET['from']) && $_GET['from']=='bind'){
                $args['from'] = 'bind';
            }
            $this->redirect_uri = add_query_arg( $args, $this->page );

            if ($_GET['action'] == 'login') {
                if(isset($_GET['redirect_to']) && $_GET['redirect_to']){
                    // 有跳转回前页，保存到session
                    WPCOM_Session::set('redirect_to', $_GET['redirect_to']);
                }
                $this->{$this->type.'_login'}();
            } else if ($_GET['action'] == 'callback') {
                if(!isset($_GET['code']) || isset($_GET['error']) || isset($_GET['error_code']) || isset($_GET['error_description'])){
                    wp_die("<h3>错误：</h3>Code获取出错，请重试！");
                    exit();
                }

                if( isset($_GET['uuid']) && $_GET['uuid'] ){
                    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width"><title>微信登录</title></head><body><p style="font-size: 18px;color:#333;text-align: center;padding-top: 100px;">登录成功，请返回电脑端继续操作！</p></body></html>';
                    WPCOM_Session::set('_'.$_GET['uuid'], $_GET['code']);
                    if(isset($_GET['redirect_to']) && $_GET['redirect_to']){
                        // 有跳转回前页，保存到session
                        WPCOM_Session::set('_'.$_GET['uuid'].'-redirect_to', $_GET['redirect_to']);
                    }
                    exit;
                }

                $this->{$this->type.'_callback'}($_GET['code']);

                $access_token = WPCOM_Session::get('access_token');
                if (!$access_token || strlen($access_token)<6 || !$this->type){
                    wp_die("<h3>错误：</h3>Token获取出错，请重试！");
                    exit();
                }

                $openid = WPCOM_Session::get('openid');
                $bind_user = $this->is_bind($this->type, $openid, WPCOM_Session::get('unionid'));
                $newuser = $this->{$this->type.'_new_user'}();
                $bind = isset($_GET['from']) && $_GET['from']=='bind' ? true : false;
                if($bind_user && $bind_user->ID){
                    if(!$bind) {
                        if (isset($newuser['nickname']))
                            update_user_option($bind_user->ID, 'social_type_' . $newuser['type'] . '_name', $newuser['nickname']);
                        wp_set_auth_cookie($bind_user->ID);
                        wp_set_current_user($bind_user->ID);
                        WPCOM_Session::delete('', 'openid');
                        WPCOM_Session::delete('', 'access_token');
                        $redirect_to = WPCOM_Session::get('redirect_to');
                        $redirect_to = $redirect_to ? $redirect_to : home_url();
                        WPCOM_Session::delete('', 'redirect_to');
                        wp_redirect($redirect_to);
                        exit;
                    }else{ // 已绑定其他用户
                        wp_die("<h3>错误：</h3>当前社交帐号已绑定本站其他用户！");
                        exit();
                    }
                }

                if(!isset($newuser['openid'])||strlen($newuser['openid'])<6){
                    wp_die("<h3>错误：</h3>OpenId获取出错，请重试！");
                    exit();
                }

                if($newuser){
                    WPCOM_Session::delete('', 'openid');
                    WPCOM_Session::set('user', json_encode($newuser));
                }
                if($this->page){
                    if($bind){
                        $user = wp_get_current_user();
                        if($user && $user->ID){
                            $newuser_id = isset($newuser['unionid']) && $newuser['unionid'] ? $newuser['unionid'] : $newuser['openid'];
                            update_user_option($user->ID, 'social_type_'.$newuser['type'], $newuser_id);
                            update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                        }else{
                            wp_die("<h3>错误：</h3>请登录后再进行绑定操作！");
                            exit();
                        }
                        WPCOM_Session::set('is_bind', $bind);
                    }
                    wp_redirect($this->page);
                }else{
                    wp_die("<h3>错误：</h3>请设置社交绑定页面（主题设置>社交登录>社交绑定页面）");
                }
                exit;
            }
        }
    }

    function body_class( $classes ){
        global $options;
        $page_id = isset($options['social_login_page']) ? $options['social_login_page'] : '';
        if( $page_id && is_page($page_id) ){
            $classes[] = 'wpcom-member member-social';
        }
        return $classes;
    }

    function unset_session(){
        global $options;
        $page_id = isset($options['social_login_page']) ? $options['social_login_page'] : '';
        if(is_page($page_id) && WPCOM_Session::get('is_bind')){
            WPCOM_Session::delete('', 'access_token');
            WPCOM_Session::delete('', 'user');
            WPCOM_Session::delete('', 'is_bind');
        }
    }

    function qq_login() {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->social['qq']['id'],
            'state' => md5(uniqid(rand(), true)),
            'scope' => 'get_user_info',
            'redirect_uri' => $this->redirect_uri
        );
        wp_redirect('https://graph.qq.com/oauth2.0/authorize?'.http_build_query($params));
        exit();
    }

    function weibo_login() {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->social['weibo']['id'],
            'redirect_uri' => $this->redirect_uri
        );
        wp_redirect('https://api.weibo.com/oauth2/authorize?'.http_build_query($params));
        exit();
    }

    function wechat_login() {
        $params = array(
            'appid' => $this->social['wechat']['id'],
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => md5(uniqid(rand(), true))
        );
        wp_redirect('https://open.weixin.qq.com/connect/qrconnect?'.http_build_query($params).'#wechat_redirect');
        exit();
    }

    function wechat2_login() {
        if( isset($_GET['uuid']) ){
            $this->redirect_uri = add_query_arg( array( 'uuid' => $_GET['uuid'] ), $this->redirect_uri );
        }
        if( isset($_GET['redirect_to']) ){
            $this->redirect_uri = add_query_arg( array( 'redirect_to' => $_GET['redirect_to'] ), $this->redirect_uri );
        }
        $params = array(
            'appid' => $this->social['wechat2']['id'],
            'redirect_uri' => apply_filters('wechat2_login_redirect_uri', $this->redirect_uri),
            'response_type' => 'code',
            'scope' => 'snsapi_userinfo',
            'state' => md5(uniqid(rand(), true))
        );
        wp_redirect('https://open.weixin.qq.com/connect/oauth2/authorize?'.http_build_query($params).'#wechat_redirect');
        exit();
    }

    function qq_callback($code) {
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->social['qq']['id'],
            'client_secret' => $this->social['qq']['key'],
            'redirect_uri' => $this->redirect_uri
        );
        $str = $this->http_request('https://graph.qq.com/oauth2.0/token?'.http_build_query($params));
        $access_token = isset($str['access_token']) ? $str['access_token'] : '';
        WPCOM_Session::set('access_token', $access_token);
        if($access_token){
            $str = $this->http_request("https://graph.qq.com/oauth2.0/me?access_token=".$access_token."&unionid=1");
            preg_match('/callback\((.*)\);/i', $str, $matches);
            $str_r = json_decode(trim($matches[1]), true);
            if(isset($str_r['error'])){
                wp_die("<h3>错误：</h3>".$str_r['error']."<h3>错误信息：</h3>".$str_r['error_description']);
                exit();
            }
            $openid = isset($str_r['openid']) ? $str_r['openid'] : '';
            WPCOM_Session::set('openid', $openid);
            if( isset($str_r['unionid']) ) WPCOM_Session::set('unionid', $str_r['unionid']);
        }else{
            preg_match('/callback\((.*)\);/i', $str, $matches);
            $str_r = json_decode(trim($matches[1]), true);
            if(isset($str_r['error'])){
                wp_die("<h3>错误：</h3>".$str_r['error']."<h3>错误信息：</h3>".$str_r['error_description']);
                exit();
            }else{
                wp_die("<h3>错误：</h3>access_token获取失败");
                exit();
            }
        }
    }

    function weibo_callback($code) {
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->social['weibo']['id'],
            'client_secret' => $this->social['weibo']['key'],
            'redirect_uri' => $this->redirect_uri
        );
        $str = $this->http_request('https://api.weibo.com/oauth2/access_token', http_build_query($params), 'POST');
        $access_token = isset($str["access_token"]) ? $str["access_token"] : '';
        $openid = isset($str["uid"]) ? $str["uid"] : '';
        WPCOM_Session::set('access_token', $access_token);
        WPCOM_Session::set('openid', $openid);
    }

    function wechat_callback($code) {
        $params = array(
            'appid' => $this->social['wechat']['id'],
            'secret' => $this->social['wechat']['key'],
            'code' => $code,
            'grant_type' => 'authorization_code'
        );
        $str = $this->http_request('https://api.weixin.qq.com/sns/oauth2/access_token', http_build_query($params), 'POST');
        $access_token = isset($str["access_token"]) ? $str["access_token"] : '';
        $openid = isset($str["openid"]) ? $str["openid"] : '';
        WPCOM_Session::set('access_token', $access_token);
        WPCOM_Session::set('openid', $openid);
        if( isset($str['unionid']) ) WPCOM_Session::set('unionid', $str['unionid']);
    }

    function wechat2_callback($code) {
        $params = array(
            'appid' => $this->social['wechat2']['id'],
            'secret' => $this->social['wechat2']['key'],
            'code' => $code,
            'grant_type' => 'authorization_code'
        );
        $str = $this->http_request('https://api.weixin.qq.com/sns/oauth2/access_token', http_build_query($params), 'POST');
        $access_token = isset($str["access_token"]) ? $str["access_token"] : '';
        $openid = isset($str["openid"]) ? $str["openid"] : '';
        WPCOM_Session::set('access_token', $access_token);
        WPCOM_Session::set('openid', $openid);
        if( isset($str['unionid']) ) WPCOM_Session::set('unionid', $str['unionid']);
    }

    function qq_new_user(){
        $client_id = $this->social['qq']['id'];
        $access_token = WPCOM_Session::get('access_token');
        $openid = WPCOM_Session::get('openid');
        $user = $this->http_request('https://graph.qq.com/user/get_user_info?access_token='.$access_token.'&oauth_consumer_key='.$client_id.'&openid='.$openid);
        $name = isset($user['nickname']) ? $user['nickname'] : 'QQ'.time();
        $return = array(
            'nickname' => $name,
            'display_name' => $name,
            'avatar' => $user['figureurl_qq_2'] ? $user['figureurl_qq_2'] : $user['figureurl_qq_1'],
            'type' => 'qq',
            'openid' => $openid
        );
        $unionid = WPCOM_Session::get('unionid');
        if( $unionid ) $return['unionid'] = $unionid;
        return $return;
    }

    function weibo_new_user(){
        $access_token = WPCOM_Session::get('access_token');
        $openid = WPCOM_Session::get('openid');
        $user = $this->http_request("https://api.weibo.com/2/users/show.json?access_token=".$access_token."&uid=".$openid);
        return array(
            'nickname' => $user['screen_name'],
            'display_name' => $user['screen_name'],
            'user_url' => 'http://weibo.com/'.$user['profile_url'],
            'avatar' => $user['avatar_large'] ? $user['avatar_large'] : $user['profile_image_url'],
            'type' => 'weibo',
            'openid' => $openid
        );
    }

    function wechat_new_user(){
        $access_token = WPCOM_Session::get('access_token');
        $openid = WPCOM_Session::get('openid');
        $user = $this->http_request("https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN");
        $return = array(
            'nickname' => $user['nickname'],
            'display_name' => $user['nickname'],
            'avatar' => $user['headimgurl'],
            'type' => 'wechat',
            'openid' => $openid,
        );
        $unionid = WPCOM_Session::get('unionid');
        if( $unionid ) $return['unionid'] = $unionid;
        return $return;
    }

    function wechat2_new_user(){
        return $this->wechat_new_user();
    }

    function wpcom_social_login(){
        $newuser = WPCOM_Session::get('user');
        $is_bind = WPCOM_Session::get('is_bind');
        $access_token = WPCOM_Session::get('access_token');
        if( !$access_token ){
            return '<p style="text-align: center;text-indent: 0;margin: 0;">社交绑定页面仅用于第三方帐号登录后帐号的绑定，如果直接访问则显示此提醒，请忽略。</p>';
        }else if( !$newuser && $access_token ){
            return '<p style="text-align: center;text-indent: 0;margin: 0;">第三方帐号返回参数错误</p>';
        }else if( !get_option('users_can_register') ){ // 未开启注册功能
            return '<p style="text-align: center;text-indent: 0;margin: 0;">' . __('User registration is currently not allowed.', 'wpcom') . '</p>';
        }else if($newuser && !is_array($newuser)){
            $newuser = json_decode($newuser, true);
        }

        $html = '<div class="social-login-wrap">';

        if($is_bind){
            $html .= '<div class="sl-info-notice" style="border-bottom: 0;padding-top: 20px;">
                        <div class="sl-info-avatar"><img class="j-lazy" src="' . $newuser['avatar'] . '" alt="' . $newuser['nickname'] . '"></div>
                        <div class="sl-info-text"><p>欢迎你，<b>' . $newuser['nickname'] . '</b>！</p>
                        <p>您的<b>' . str_replace('登录', '帐号', $this->social[$newuser['type']]['title']) . '</b>已绑定成功，后续将可以直接使用<b>' . str_replace('登录', '帐号', $this->social[$newuser['type']]['title']) . '</b>登录本站！</p></div>
                    </div>';
        }else {
            $html .= '<div class="sl-info-notice">
                        <div class="sl-info-avatar"><img class="j-lazy" src="' . $newuser['avatar'] . '" alt="' . $newuser['nickname'] . '"></div>
                        <div class="sl-info-text"><p>欢迎你，<b>' . $newuser['nickname'] . '</b>！</p>
                        <p>当前你正在使用<b>' . $this->social[$newuser['type']]['title'] . '</b>帐号登录，请绑定已有帐户，或者注册新用户绑定。</p></div>
                    </div>
                    <div class="social-login-form">

                    <div class="sl-form-item">
                    <form id="sl-form-create" class="sl-info-form" method="post"><div id="sl-info-nonce">
                        ' . wp_nonce_field('wpcom_social_login', 'social_login_nonce', true, false) . '</div>
                        <h3 class="sl-form-title">已经拥有帐户，请绑定</h3>
                        <div class="sl-input-item">
                            <label>用户名或邮箱</label>
                            <div class="sl-input">
                                <input type="text" name="username" value="" placeholder="'.(is_wpcom_enable_phone()?'请输入手机号码/电子邮箱/用户名':'请输入用户名或邮箱').'">
                            </div>
                        </div>
                        <div class="sl-input-item">
                            <label>密码</label>
                            <div class="sl-input">
                                <input type="password" name="password" value="" placeholder="请输入密码">
                            </div>
                        </div>
                        <div class="sl-input-item sl-submit">
                            <div class="sl-result pull-left"></div>
                            <input class="btn sl-input-submit" type="submit" value="登录并绑定">
                        </div>
                    </form>
                    </div>
                    
                    <div class="sl-form-item">
                    <form id="sl-form-bind" class="sl-info-form" method="post"><div id="sl-info2-nonce">
                    ' . wp_nonce_field('wpcom_social_login2', 'social_login2_nonce', true, false) . '</div>
                    <h3 class="sl-form-title">没有帐户，我是新用户</h3>
                        <div class="sl-input-item sl-submit" style="text-align: left">
                            <div class="sl-result pull-left"></div>
                            <input class="btn sl-input-submit" type="submit" style="padding: 10px 30px;
    font-size: 16px;display: block;width: 100%;margin-top: 30px;" value="点击立即登录">
                        </div>
                    </form>
                    </div></div>';
        }
        $html .= '</div>';

        return $html;
    }

    function login_to_bind(){
        check_ajax_referer( 'wpcom_social_login', 'social_login_nonce', false );

        $newuser = WPCOM_Session::get('user');

        if(!$newuser){
            echo json_encode(array('result'=> 3));
            exit;
        }else if($newuser && !is_array($newuser)){
            $newuser = json_decode($newuser, true);
        }

        if( ! (isset($newuser['type']) || $newuser['openid']) ){
            echo json_encode(array('result'=> 3));
            exit;
        }

        $res = array();

        if(isset($_POST['username'])){
            $username = $_POST['username'];
        }
        if(isset($_POST['password'])){
            $password = $_POST['password'];
        }

        if($username==''||$password=='') {
            $res['result'] = 1;
        }

        $user = wp_authenticate($username, $password);

        if(is_wp_error( $user )){
            $res['result'] = 2;
        }else{
            $bind_user = $this->is_bind($newuser['type'], $newuser['openid'], isset($newuser['unionid'])?$newuser['unionid']:'');
            if(isset($bind_user->ID) && $bind_user->ID){ // 已绑定用户
                if( (is_email($username) && $bind_user->data->user_email==$username) ||
                    (!is_email($username) && $bind_user->data->user_login==$username) ){ // 绑定的就是这个帐号
                    $res['result'] = 0;
                    $res['redirect'] = home_url();
                    WPCOM_Session::delete('', 'user');
                    update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                    wp_set_auth_cookie($user->ID);
                    wp_set_current_user($user->ID);
                }else{
                    $res['result'] = 4;
                }
            }else{
                $newuser_id = isset($newuser['unionid']) && $newuser['unionid'] ? $newuser['unionid'] : $newuser['openid'];
                update_user_option($user->ID, 'social_type_'.$newuser['type'], $newuser_id);
                update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                $res['result'] = 0;
                $res['redirect'] = home_url();
                WPCOM_Session::delete('', 'user');
                wp_set_auth_cookie($user->ID);
                wp_set_current_user($user->ID);
                $this->set_avatar($user->ID, $newuser['avatar']);
            }
        }

        echo json_encode($res);
        exit;
    }

    function create(){
        check_ajax_referer( 'wpcom_social_login2', 'social_login2_nonce', false );

        $newuser = WPCOM_Session::get('user');

        if(!$newuser){
            echo json_encode(array('result'=> 3));
            exit;
        }else if($newuser && !is_array($newuser)){
            $newuser = json_decode($newuser, true);
        }

        if( ! (isset($newuser['type']) || $newuser['openid']) ){
            echo json_encode(array('result'=> 3));
            exit;
        }

        $res = array();

        $newuser_id = isset($newuser['unionid']) && $newuser['unionid'] ? $newuser['unionid'] : $newuser['openid'];

        if(isset($_POST['email']) && $_POST['email']){
            $email = $_POST['email'];
        }else{
            $email = $newuser_id . '@email.empty';
        }

        if($email=='') $res['result'] = 1;

        if(is_email($email)){
            $bind_user = $this->is_bind($newuser['type'], $newuser['openid'], isset($newuser['unionid'])?$newuser['unionid']:'');
            if(isset($bind_user->ID) && $bind_user->ID){ // 已绑定用户
                $res['result'] = 4;
            }else{
                $user = get_user_by( 'email', $email );
                if($user->ID){ // 用户已存在
                    $res['result'] = 5;
                }else{
                    $res['result'] = 0;
                    $res['redirect'] = home_url();

                    $userdata = array(
                        'user_pass' => wp_generate_password(),
                        'user_login' => strtoupper($newuser['type']).$newuser['openid'],
                        'user_email' => $email,
                        'nickname' => $newuser['nickname'],
                        'display_name' => $newuser['display_name']
                    );
                    if($newuser['type']=='weibo') $userdata['user_url'] = $newuser['user_url'];

                    if(!function_exists('wp_insert_user')){
                        include_once( ABSPATH . WPINC . '/registration.php' );
                    }
                    $user_id = wp_insert_user($userdata);

                    if ( ! is_wp_error( $user_id ) ) {
                        wp_update_user( array( 'ID'=>$user_id, 'role'=>'contributor' ) );

                        do_action('wpcom_social_new_user', $user_id, $_POST);
                        update_user_option($user_id, 'social_type_'.$newuser['type'], $newuser_id);
                        update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                        WPCOM_Session::delete('', 'user');
                        wp_set_auth_cookie($user_id);
                        wp_set_current_user($user_id);
                        $this->set_avatar($user_id, $newuser['avatar']);
                    }else{
                        $res['result'] = 6;
                        $res['msg'] = $user_id->get_error_message();
                    }
                }
            }
        }else{
            $res['result'] = 2;
        }

        echo json_encode($res);
        exit;
    }

    function http_request($url, $body=array(), $method='GET'){
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

    function is_bind($type, $openid, $unionid = '') {
        global $wpdb;
        if( $type == 'wechat2' ) $type = 'wechat';

        if( ($type=='wechat' || $type=='qq') && $unionid!='' ){
            $args = array(
                'meta_key'     => $wpdb->get_blog_prefix() . 'social_type_' . $type,
                'meta_value'   => $unionid,
            );
            $users = get_users($args);

            // unionid找不到用户，则使用openid
            if( !$users ){
                $args['meta_value'] = $openid;
                $users = get_users($args);
                if( $users ){ // 能找到用户，则更新为unionid
                    $user = $users[0];
                    update_user_option($user->ID, 'social_type_'.$type, $unionid);
                    return $user;
                }
            }
        }else{
            $args = array(
                'meta_key'     => $wpdb->get_blog_prefix() . 'social_type_' . $type,
                'meta_value'   => $openid,
            );

            $users = get_users($args);
        }
        if( $users ){
            return $users[0];
        }
    }

    function wechat2_login_check(){
        $res = array();
        $uuid = sanitize_key(isset($_POST['uuid']) ? $_POST['uuid'] : '');
        if( $uuid && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ){
            $code = WPCOM_Session::get('_'.$uuid);
            if($code){
                $res['result'] = 0;
                $args = array( 'type'=>'wechat2', 'action'=>'callback', 'code' => $code );
                if(isset($_SERVER['HTTP_REFERER']) && preg_match('/bind/i', $_SERVER['HTTP_REFERER'])){
                    $args['from'] = 'bind';
                }
                $res['redirect_to'] = add_query_arg( $args, $this->page );
                if(isset($_GET['redirect_to']) && $_GET['redirect_to']){
                    // 有跳转回前页，保存到session
                    WPCOM_Session::set('redirect_to', $_GET['redirect_to']);
                }
                $redirect_to = WPCOM_Session::get('_'.$uuid.'-redirect_to');
                if($redirect_to){ // 有跳转回前页，保存到session
                    WPCOM_Session::set('redirect_to', $redirect_to);
                    WPCOM_Session::delete('', '_'.$uuid.'-redirect_to');
                }
            }else{
                $res['result'] = 1;
            }
        }else{
            $res['result'] = 2;
        }
        echo json_encode($res);
        exit;
    }

    function set_temp_database(){
        global $wpdb;
        $table = $wpdb->prefix.'wpcom_temp';

        if( $wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ){
            $charset_collate = $wpdb->get_charset_collate();

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            // 缓存表
            $create_sql = "CREATE TABLE $table (".
                "ID BIGINT(20) NOT NULL auto_increment,".
                "name text NOT NULL,".
                "value longtext NOT NULL,".
                "time datetime,".
                "PRIMARY KEY (ID)) $charset_collate;";

            dbDelta( $create_sql );
        }
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