(function($){
    $('#wpcom-plugin-panel').on('click', '.j-wwa-commit', function(){
        var $el = $(this);
        if($el.hasClass('loading')) return false;
        $el.addClass('loading');
        $.ajax({
            url: ajaxurl,
            data: {action: 'WWA_commit'},
            dataType: 'json',
            success: function(res){
                $el.removeClass('loading');
                if(res.res==0){
                    alert('代码提交成功');
                }else{
                    alert( res.res + ':' + res.msg);
                }
            },
            error: function(){
                $el.removeClass('loading');
                alert('请求出错，请重试');
            }
        })
    }).on('click', '.j-wwa-submit', function(){
        var $el = $(this);
        if($el.hasClass('loading')) return false;
        $el.addClass('loading');
        if(confirm('下次提交需要等本次审核通过后才能再次提交（审核一般需要1-3个工作日），建议您扫码体验后确定没问题再提交审核，请确定是否继续本次提交？')){
            $.ajax({
                url: ajaxurl,
                data: {action: 'WWA_submit_audit'},
                dataType: 'json',
                success: function(res){
                    $el.removeClass('loading');
                    if(res.res==0){
                        alert('提交审核成功');
                    }else{
                        alert( res.res + ':' + res.msg);
                    }
                },
                error: function(){
                    $el.removeClass('loading');
                    alert('请求出错，请重试');
                }
            })
        }else{
            $el.removeClass('loading');
        }
    }).on('click', '.j-wwa-release', function(){
        var $el = $(this);
        if($el.hasClass('loading')) return false;
        $el.addClass('loading');
        if(confirm('温馨提示：由第三方发布上线的小程序微信会重置服务器域名，请发布上线后立即到小程序后台【开发-开发设置-服务器域名】添加自己网站域名；另外服务器域名微信限制每月可以修改5次，建议您每月发布上线操作次数控制在5次以内')){
            $.ajax({
                url: ajaxurl,
                data: {action: 'WWA_release'},
                dataType: 'json',
                success: function(res){
                    $el.removeClass('loading');
                    if(res.res==0){
                        alert('发布成功');
                    }else{
                        alert( res.res + ':' + res.msg);
                    }
                },
                error: function(){
                    $el.removeClass('loading');
                    alert('请求出错，请重试');
                }
            })
        }else{
            $el.removeClass('loading');
        }
    });
})(jQuery);