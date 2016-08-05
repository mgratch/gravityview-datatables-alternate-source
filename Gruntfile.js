module.exports = function(grunt) {

	// Only need to install one package and this will load them all for you. Run:
	// npm install --save-dev load-grunt-tasks
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		jshint: [
			"includes/assets/js/gaddon_fieldmap.js",
			"includes/assets/js/sort-filter-selectbox.js",
		],

		uglify: {
			options: {
				mangle: false
			},
			main: {
				files: [{
		          expand: true,
		          cwd: 'includes/assets/js',
		          src: ['**/*.js','!**/*.min.js'],
		          dest: 'includes/assets/js',
		          ext: '.min.js'
		      }]
			}
        },

		watch: {
			scripts: {
				files: ['includes/assets/js/*.js','!includes/assets/js/*.min.js'],
				tasks: ['uglify:main','newer:jshint']
			}
		},

		dirs: {
			lang: 'languages'
		},

		// Convert the .po files to .mo files
		potomo: {
			dist: {
				options: {
					poDel: false
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.lang %>',
					src: ['*.po'],
					dest: '<%= dirs.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		// Pull in the latest translations
		exec: {
			transifex: 'tx pull -a',

			// Create a ZIP file
			zip: 'git-archive-all ../gravityview-datatables-alternate-source.zip',

			bower: 'bower install'
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'gravityview-datatables-alternate-source.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: ['node_modules/.*', 'assets/.*', 'tmp/.*', 'vendor/.*', 'includes/lib/xml-parsers/.*', 'includes/lib/jquery-cookie/.*', 'includes/lib/standalone-phpenkoder/.*' ],
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					processPot: function( pot, options ) {
						pot.headers['language'] = 'en_US';
						pot.headers['language-team'] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers['last-translator'] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers['report-msgid-bugs-to'] = 'https://gravityview.co/support/';

						var translation,
							excluded_meta = [

								'GravityView DataTables Alternative Source (BETA)',
								'All an alternative source to be set for Gravity View DataTables Extension',
								'https://gravityview.co',
								'Katz Web Services, Inc.',
								'https://www.katzwebservices.com'
							];

						for ( translation in pot.translations[''] ) {
							if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
								if ( excluded_meta.indexOf( pot.translations[''][ translation ].msgid ) >= 0 ) {
									console.log( 'Excluded meta: ' + pot.translations[''][ translation ].msgid );
									delete pot.translations[''][ translation ];
								}
							}
						}

						return pot;
					}
				}
			}
		},

		// Add textdomain to all strings, and modify existing textdomains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gravityview-datatables-alternate-source',    // Project text domain.
				updateDomains: [ 'gravityview-datatables-alternate-source', 'gravityview', 'gravity-view', 'gravityforms', 'edd_sl', 'edd' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!tmp/**'
					]
				}
			}
		}
	});

	// Still have to manually add this one...
	grunt.loadNpmTasks('grunt-wp-i18n');

	// Regular CSS/JS/Image Compression stuff
	grunt.registerTask( 'default', [ 'exec:bower', 'uglify', 'watch' ] );

	// Translation stuff
	grunt.registerTask( 'translate', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );

};
