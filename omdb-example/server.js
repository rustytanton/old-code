// this server just for dev purposes
var static = require('node-static');
var file = new static.Server('./');

require('http').createServer(function (request, response) {
    request.addListener('end', function () {
        file.serve(request, response, function(e, res) {
        	if (e && (e.status === 404)) {
                file.serveFile('/index.html', 404, {}, request, response);
            }
        });
    }).resume();
}).listen(8080);