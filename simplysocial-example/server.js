var app, express;

express = require("express");
app = express();

app.use('/vendor', express.static(__dirname + '/bower_components'));
app.use(express.static(__dirname + '/public'));

app.get(/^\/(followers|following|profile|settings)/, function(req, res) {
    res.sendFile(__dirname + '/public/index.html');
});

console.log("simplysocial app listening on port 3000 (http://localhost:3000)");
app.listen(3000);