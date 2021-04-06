module.exports = {
	// prefix: 'tw-',
	purge: {
		enabled: true,
		content: [
			'./assets/src/**/*.vue',
			'./assets/src/**/*.js',
			'./assets/src/**/*.jsx',
			'./assets/src/**/*.scss',
		]
	},
	darkMode: false, // or 'media' or 'class'
	theme: {
		extend: {},
		screens: {
			'sm': {'max': '767px'},
			'md': {'min': '768px'},
			'lg': {'min': '1024px'},
			'xl': {'min': '1280px'},
			'2xl': {'min': '1536px'},
		},
	},
	variants: {
		extend: {},
	},
	plugins: [],
	corePlugins: {
		preflight: false,
	}
}
