var remote = new RemoteAPI();
function success(data, response) {
    ResponseToHTML(data.message, response);
}
function error(response) {
    var body = response.responseJSON;
    ResponseToHTML(body.message, response);
}
function ResponseToHTML(data, response) {
    $('.response-headers').html('{headers}');
    $('.response-body').html('{result}');

    var header = response.getAllResponseHeaders();
    var headerHtml = header.replace(/\n/gi, "<br>");
    $('.response-headers').html(headerHtml);
    $('.response-body').html(JSON.stringify(data));
}

$(document).ready(function () {
    remote.Listen('broadcast', null, 'GET', 5000,
    function (data) {console.log(data);},
    function (data) {console.log(data.responseJSON);} , true);
});