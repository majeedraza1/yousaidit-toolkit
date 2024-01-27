export default {
    content: [
        './resources/**/*.vue',
        './resources/**/*.js',
        './resources/**/*.jsx',
        './resources/**/*.ts',
        './resources/**/*.tsx',
        './resources/**/*.scss',
    ],
    theme: {
        extend: {
            colors: {
                "primary": '#e42f57',
                "on-primary": '#ffffff',
            },
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
