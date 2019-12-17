function $_http(httpMethod, url, data, successCb) {
    var jsonData=null;

    httpMethod = httpMethod.toLowerCase();
    httpMethod == 'post' ? jsonData = JSON.stringify(data) : jsonData=data;
    $.ajax({
        type: httpMethod,
        cache: true,
        headers: {
            'content-type': "application/json",
        },
        dataType: "json", //返回值类型
        data: jsonData,
        url: url,
        success: successCb,
    },'json');
}