es6 jasmine
===========

ES6 project which:
*	Loads in a browser with dynamic transpiling using [SystemJS](https://github.com/systemjs/systemjs)
*	Loads from an ES5 file compiled with [SystemJS Builder](https://github.com/systemjs/builder)
*	Runs Jasmine unit tests written in ES6 style using [karma-systemjs](https://github.com/rolaveric/karma-systemjs/), sharing config with production app

Set-up:
*	Install [nodejs](http://nodejs.org)
*	Install global utilities: `npm install -g karma bower`
*	Install project dependencies: `npm install && bower install`

View app demo:
*	Run `node demo/server.js` from project root
*	Visit `http://localhost:3000/demo/dynamic.html` or `http://localhost:3000/demo/compiled.html` in a browser

Compile:
*	Run `grunt es6`

Execute tests:
*	Run `karma start`