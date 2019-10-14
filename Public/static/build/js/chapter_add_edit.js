$(function(){
    // 获得url参数
    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = window.location.search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]); return null; //返回参数值
    }
    var para = getUrlParam('isEdit');
    // isEdit有值说明是编辑
    if (para) {
        $('#add_edit_course h3').text('编辑章节');
    }
    // 根据文件类型选择，切换显示“文字”、“ppt”、“视频”上传框
    var radio = $('input[name="type"]');
    radio.change(function(){
        var index = radio.index(this);

        if (index == 2) {
            index = 1;
        }
        // $('.toggleShow').eq(index).show().siblings('.toggleShow').hide();
        if (index == 0) {
            $('.toggleShow').eq(0).show();
            $('.toggleShow').eq(1).hide();
            //$('.twoBtn').css('margin-top', '20px');
        } else if (index == 1) {
            $('.toggleShow').eq(1).show();
            $('.toggleShow').eq(0).hide();
            //$('.twoBtn').css('margin-top', '70px');
        }
    })
    // webuploader.js初始化
    var uploader = WebUploader.create({

        // swf文件路径
        swf: '/static/build/js/Uploader.swf',

        // 文件接收服务端。
        server: '/manage/course_detail/upload',

        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: {
            id: '#picker',
            multiple:false, 
            label: '选择文件'
        },
        fileNumLimit: 1,
        // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
        resize: false,
        // 开起分片上传。
        chunked: true
    });
    // 当有文件被添加进队列的时候
    uploader.on( 'fileQueued', function( file ) {
        $('#thelist').empty();
        $('#thelist').append( '<div id="' + file.id + '" class="item">' +
            '<h4 class="info">' + file.name + '</h4>' +
            '<p class="state"></p>' +
        '</div>' );
        uploader.upload();
    });
    // 文件上传过程中创建进度条实时显示。
    /*uploader.on( 'uploadProgress', function( file, percentage ) {
        var $li = $( '#'+file.id ),
            $percent = $li.find('.progress .progress-bar');

        // 避免重复创建
        if ( !$percent.length ) {
            $percent = $('<div class="progress progress-striped active">' +
            '<div class="progress-bar" role="progressbar" style="width: 0%">' +
            '</div>' +
            '</div>').appendTo( $li ).find('.progress-bar');
        }

        $li.find('p.state').text('上传中');

        $percent.css( 'width', percentage * 100 + '%' );
    })*/
    uploader.on( 'uploadSuccess', function( file , response ) {
        if (response.code != undefined) {
            alert(response.msg);
            return false;
        }
        $('#filePath').val(response.name);
        $( '#'+file.id ).find('p.state').text('已上传');
        uploader.removeFile(file);
    });
    
    uploader.on( 'uploadError', function( file ) {
        $( '#'+file.id ).find('p.state').text('上传出错');
    });
    
    uploader.on( 'uploadComplete', function( file ) {
        $( '#'+file.id ).find('.progress').fadeOut();
    });
    $('.stop').click(function(){
        var file = uploader.getFiles()[0];
        $( '#'+file.id ).find('p.state').text('暂停中');
        uploader.stop(true);
    })
    $('.upload').click(function(){
        var file = uploader.getFiles()[0];
        $( '#'+file.id ).find('p.state').text('上传中');
        uploader.upload();
    })
    $('.cancel').click(function(){
        $('#thelist').empty();
        uploader.reset()
    })
    $('#ctlBtn').click(function(){
        uploader.upload();
    })
})

