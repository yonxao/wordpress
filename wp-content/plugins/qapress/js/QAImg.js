tinymce.PluginManager.add('QAImg', function(editor, url) {

    var $el = jQuery(editor.getElement()).parent();
    var timer = null;

    var input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');

    function notice(type, msg, time){
        clearTimeout(timer);
        jQuery('#notice').remove();
        $el.append('<div id="notice"><div class="notice-bg"></div><div class="notice-wrap"><div class="notice-inner notice-'+type+'">'+msg+'</div></div></div>');
        if(time) {
            timer = setTimeout(function () {
                jQuery('#notice').remove();
            }, time);
        }
    }

    function img_post() {
        var fd = new FormData();
        fd.append( "upfile", input.files[0]);
        fd.append( "action", 'QAPress_img_upload');      
        jQuery.ajax({
            type: 'POST',
            url: QAPress_js.ajaxurl,
            data: fd, 
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data, textStatus, XMLHttpRequest) {
                clearTimeout(timer);
                jQuery('#notice').remove();
                if(data.result=='0'){
                    editor.insertContent( '<img src="'+data.image.url+'" alt="'+data.image.alt+'">' );
                }else{
                    notice(0, '图片上传出错，请稍后再试！', 1200);
                }
            },
            error: function(MLHttpRequest, textStatus, errorThrown) {
                clearTimeout(timer);
                jQuery('#notice').remove();
                alert(errorThrown);
            }
        });
    }

    input.onchange = function() {
        var file = this.files[0];

        if(file){
            if(!/\.(gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)$/.test(file.name)){
                notice(0, '仅支持上传jpg、png、gif格式的图片文件', 2000);
                return false;
            }else if(file.size > 2 * 1024 * 1024){
                notice(0, '图片大小不能超过2M', 1500);
                return false;
            }else{
                img_post();
                notice(1, '<img class="notice-loading" src="'+QAPress_js.ajaxloading+'"> 正在上传...', 0);
            }
        }
    };


    editor.addButton('QAImg', {
        text: '',
        icon: 'image',
        tooltip: "上传图片",
        classes: 'qaimg',
        onclick: function() {
            if( ! /Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent) ) {
                input.click();
            }
        },
        onTouchEnd: function(){
            if( /Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent) ) {
                input.click();
            }
        }
    });
});