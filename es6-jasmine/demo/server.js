var express = require('express'),
	app = express();

app.use(express.static('.'));

console.log('Listening on localhost:3000...');
app.listen('3000','localhost');