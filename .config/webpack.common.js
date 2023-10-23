const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserPlugin = require('terser-webpack-plugin');
const {VueLoaderPlugin} = require('vue-loader');
const svgToMiniDataURI = require('mini-svg-data-uri');

const commonConfig = (env, argv) => {
    let isDev = argv.mode !== 'production';

    let plugins = [];

    plugins.push(new MiniCssExtractPlugin({
        filename: "../css/[name].css"
    }));

    plugins.push(new VueLoaderPlugin());

    return {
        "devtool": isDev ? 'eval' : false,
        "module": {
            "rules": [
                {
                    test: /\.(js|jsx|ts|tsx)$/i,
                    use: {
                        loader: "babel-loader",
                        options: {
                            presets: [
                                '@babel/preset-env',
                                '@babel/preset-react',
                                "@babel/preset-typescript"
                            ],
                            plugins: [
                                ['@babel/plugin-proposal-class-properties'],
                                ['@babel/plugin-proposal-private-methods'],
                                ['@babel/plugin-proposal-object-rest-spread'],
                            ],
                        }
                    }
                },
                {
                    test: /\.vue$/i,
                    use: [
                        {loader: 'vue-loader'}
                    ]
                },
                {
                    test: /\.(sass|scss|css)$/i,
                    use: [
                        isDev ?
                            {loader: "style-loader"} :
                            {
                                loader: MiniCssExtractPlugin.loader,
                                options: {publicPath: ''}
                            },
                        {
                            loader: "css-loader",
                            options: {
                                sourceMap: isDev,
                                importLoaders: 1
                            }
                        },
                        {
                            loader: "postcss-loader",
                            options: {
                                sourceMap: isDev,
                                postcssOptions: {
                                    plugins: [
                                        ['postcss-preset-env'],
                                        ['tailwindcss'],
                                    ],
                                },
                            },
                        },
                        {
                            loader: "sass-loader",
                            options: {
                                sourceMap: isDev,
                            },
                        }
                    ]
                },
                {
                    test: /\.(eot|ttf|woff|woff2)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: '../fonts/[hash][ext]'
                    }
                },
                {
                    test: /\.(png|je?pg|gif|webp)$/i,
                    type: 'asset',
                    generator: {
                        filename: '../images/[hash][ext]'
                    }
                },
                {
                    test: /\.svg$/i,
                    type: 'asset',
                    generator: {
                        filename: '../images/[hash][ext]',
                        dataUrl: content => svgToMiniDataURI(content.toString())
                    },
                }
            ]
        },
        optimization: {
            minimizer: [
                new TerserPlugin(),
                new CssMinimizerPlugin()
            ],
        },
        resolve: {
            alias: {
                'vue$': 'vue/dist/vue.esm.js',
                '@': path.resolve('./resources/'),
            },
            modules: [
                path.resolve('./node_modules'),
                path.resolve('./resources'),
                path.resolve('./resources/shapla'),
            ],
            extensions: ['*', '.js', '.vue', '.json', '.ts', '.tsx']
        },
        plugins: plugins,
        externals: {
            'jquery': 'jQuery',
            'react': 'React',
            'react-dom': 'ReactDOM',
            '@wordpress/blocks': 'wp.blocks',
            '@wordpress/block-editor': 'wp.blockEditor',
            '@wordpress/components': 'wp.components'
        }
    }
}

module.exports = commonConfig;
