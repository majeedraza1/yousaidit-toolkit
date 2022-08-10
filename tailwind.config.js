module.exports = {
	// prefix: 'tw-',
	purge: {
		// enabled: true,
		content: [
			'./resources/**/*.vue',
			'./resources/**/*.js',
			'./resources/**/*.jsx',
			'./resources/**/*.scss',
		]
	},
	darkMode: false, // or 'media' or 'class'
	theme: {
		extend: {
			colors: {
				"primary": '#e42f57',
				"on-primary": '#ffffff',
			},
		},
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
