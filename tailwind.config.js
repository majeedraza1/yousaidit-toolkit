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
	},
	variants: {
		extend: {},
	},
	plugins: [],
	corePlugins: {
		preflight: false,
	}
}
