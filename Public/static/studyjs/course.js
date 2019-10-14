$(function(){
    // 如果url参数带course_id，打开添加课程div
    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = window.location.search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]); return null; //返回参数值
    }
    var para = getUrlParam('course_id');
    // isEdit有值说明是编辑
    if (para) {
        $('#add_course_win').show();
    }
})
// 添加更多子账号
function add_more(){
    var length = $('.form-group').length;
    $('.form-group:last').before('<div class="form-group"><label for="account'+length+'" class="hidden-xs control-label col-md-3 col-sm-3 col-xs-12">手机号</label><div class="col-md-6 col-sm-6 col-xs-12 line"><input type="text" name="account'+length+'" id="account'+length+'" class="form-control col-md-7 col-xs-12 parsley-success" placeholder="请填写子账号手机号"><span class="glyphicon glyphicon-trash" onclick="del_account('+length+')"></span></div></div>');
}
// 删除子账号
function del_account(i){
    console.log(i)
    $('.form-group:nth-child('+i+')').remove();
    // 剩下的form-group
    var groups = $('.form-group');
    for(var i = 0; i < groups.length; i++) {
        $(groups[i]).find('label').attr('for', 'account'+(i+1));
        $(groups[i]).find('.form-control').attr('name', 'account'+(i+1)).attr('id', 'account'+(i+1));
        $(groups[i]).find('.glyphicon.glyphicon-trash').attr('onclick', 'del_account('+(i+1)+')');
    }
}
