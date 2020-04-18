module.exports = function(config) {
	config.set({
		autoWatch: true,
		baseURL: '',
		browsers: ['PhantomJS'],
		captureConsole: true,
		files: [],
		frameworks: ['systemjs','jasmine'],
		plugins: [
			require('karma-jasmine'),
			require('karma-phantomjs-launcher'),
			require('karma-systemjs')
		],
		systemjs: {
			configFile: './src/js/config.js',
			config: {
				/** @todo ideally wouldn't have to manually map jquery */
				map: {
					'jquery': 'bower_components/jquery/dist/jquery.js'
				},
				transpiler: 'babel'
			},
			files: [
				'bower_components/jquery/dist/jquery.js',
				'src/js/!(main).js',
				'src/js/**/*.js',
				'spec/*.js'
			]
		}
	});
};