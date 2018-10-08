const sass = require('node-sass');

module.exports = function(grunt) {
	grunt.initConfig({

		concat : {
			'vendor-prod' : {
				src: [
					'node_modules/vue/dist/vue.min.js',
					'node_modules/vue-router/dist/vue-router.min.js'
				],
				dest: 'js/vendor.js'
			},
			'vendor-dev' : {
				src: [
					'node_modules/vue/dist/vue.js',
					'node_modules/vue-router/dist/vue-router.js'
				],
				dest: 'js/vendor.js'
			}
		},

		sass: {
			options: {
				implementation : sass,
				sourcemap : false
			},
			dist: {
				files: {
					'css/user_ldap_settings.css': 'src/styles/settings.scss'
				}
			}
		},

		browserify: {
			dist: {
				files: {
					// destination for transpiled js : source js
					'js/user_ldap_settings.js': 'src/scripts/settings.js'
				},
				options: {
					transform: [
						['babelify', { presets: 'es2015'}],
						['vueify']

					],
					browserifyOptions: {
						debug: true
					}
				}
			}
		},

		watch: {
			default: {
				options: {
					spawn: false
				},
				files: [
					'src/**/*.js',
					'src/**/*.scss',
					'src/**/*.vue'
				],
				tasks: [
					'force:on',
					'sass',
					'browserify',
					'force:off'
				]
			}
		}
	}); //initConfig
	//
	grunt.loadNpmTasks('grunt-force');
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-browserify');

	grunt.registerTask('default', [
		'concat:vendor-prod',
		'sass',
		'browserify'
	]);

	grunt.registerTask('watcher', [
		'concat:vendor-dev',
		'sass',
		'browserify',
		'watch'
	]);
};
