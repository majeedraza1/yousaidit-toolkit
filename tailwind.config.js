module.exports = {
	// prefix: 'tw-',
	purge: {
		enabled: false,
		content: [
			'./resources/**/*.vue',
			'./resources/**/*.js',
			'./resources/**/*.jsx',
			'./resources/**/*.scss',
		]
	},
	darkMode: false, // or 'media' or 'class'
	theme: {
		extend: {},
	},
	variants: {
		extend: {},
	},
	plugins: [],
	corePlugins: {
		preflight: false,
	}
}
