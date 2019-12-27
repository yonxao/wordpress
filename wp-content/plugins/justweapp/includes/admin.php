<?php
add_action( 'wp_ajax_WWA_admin_html', 'WWA_admin_html' );
function WWA_admin_html(){
    global $WWA;
    $auth_query = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token')
    );
    $server = 'https://www.wpcom.cn/weixin-open';
    $auth_url = $server . '/auth/'. $WWA->info['plugin_id'] . '?' . http_build_query($auth_query);
    $qrcode_url = $server . '/qrcode/'. $WWA->info['plugin_id'] . '?' . http_build_query($auth_query);
    ?>
    <div class="wwa-upload">
        <h2 class="wwa-title-h2"><span>小程序发布流程</span></h2>
        <table class="wwa-upload-wrap">
            <tr>
                <th class="wwa-upload-step-title">
                    <div class="wwa-upload-step-no">第一步</div>
                    微信授权
                </th>
                <th class="wwa-upload-step-title">
                    <div class="wwa-upload-step-no">第二步</div>
                    提交代码
                </th>
                <th class="wwa-upload-step-title">
                    <div class="wwa-upload-step-no">第三步</div>
                    扫码体验
                </th>
                <th class="wwa-upload-step-title">
                    <div class="wwa-upload-step-no">第四步</div>
                    提交审核
                </th>
                <th class="wwa-upload-step-title">
                    <div class="wwa-upload-step-no">第五步</div>
                    发布上线
                </th>
            </tr>
            <tr>
                <td class="wwa-upload-step-desc">
                    <div class="wwa-upload-notice"><b>仅第一次提交前需要授权</b>，后续无需再次授权（如解除过授权也需要再次进行授权）</div>
                    <div class="wwa-upload-notice">小程序授权绑定并提交代码后不可修改，请检查绑定的小程序是否正确</div>
                </td>
                <td class="wwa-upload-step-desc">
                    <div class="wwa-upload-notice">代码提交会自动获取小程序最新版本代码并提交到微信平台，如小程序有更新可执行此操作</div>
                </td>
                <td class="wwa-upload-step-desc">
                    <div class="wwa-upload-notice">扫码体验可<b>预览上一次代码提交的效果</b>，如不满意可修改设置信息重新提交预览体验，如果满意的话可以进行下一步提交审核</div>
                    <div class="wwa-upload-notice">点击以下按钮获取二维码后使用微信扫码体验</div>
                </td>
                <td class="wwa-upload-step-desc">
                    <div class="wwa-upload-notice">提交微信官方审核，正常情况下会在<b>1-3个工作日内审核完成</b>，请体验后确定没问题再提交，否则<b>提交后需要审核通过后才能再次提交</b>，审核完成后会通知小程序绑定的微信，留意微信通知即可。</div>
                </td>
                <td class="wwa-upload-step-desc">
                    <div class="wwa-upload-notice"><b>重要：</b>根据微信规则，<b>授权给第三方的小程序，通过第三方发布上线后原先自己配置的服务器域名将被删除，只保留第三方平台域名</b>，请发布上线后立即到<b>开发-开发设置-服务器域名</b>，添加自己网站域名</div>
                </td>
            </tr>
            <tr>
                <td class="wwa-upload-step-action">
                    <a class="button button-wechat" href="<?php echo $auth_url;?>" target="_blank">前往微信授权</a>
                </td>
                <td class="wwa-upload-step-action">
                    <a class="button button-wechat j-wwa-commit" href="javascript:;">提交代码</a>
                </td>
                <td class="wwa-upload-step-action">
                    <a class="button button-wechat" href="<?php echo $qrcode_url;?>" target="_blank">获取体验二维码</a>
                </td>
                <td class="wwa-upload-step-action">
                    <a class="button button-wechat j-wwa-submit" href="javascript:;">立即提交审核</a>
                </td>
                <td class="wwa-upload-step-action">
                    <a class="button button-wechat j-wwa-release" href="javascript:;">发布上线</a>
                </td>
            </tr>
        </table>
    </div>
    <?php  exit;
}