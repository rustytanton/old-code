module.exports = function(grunt) {

    'use strict';

    grunt.registerTask('es6', ['systemjs','es6templates']);

    require('./src/js/temp/bsp-grunt')(grunt, {
        systemjs: {
            dist: {
                options: {
                    configFile: 'src/js/config.js',
                    configOverrides: {
                        baseURL: '.'
                    }
                },
                files: [
                    { 'dist/main.js': 'src/js/main.js' }
                ]
            }
        },
        es6templates: {
            dist: {
                files: [
                    {
                        cwd: 'src/js/templates',
                        expand: true,
                        ext: '.html',
                        dest: 'dist/templates/',
                        src: '**/*.js'
                    }
                ]
            }
        }
    });

};
