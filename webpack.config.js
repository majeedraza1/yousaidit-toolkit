const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const TerserPlugin = require('terser-webpack-plugin');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const autoprefixer = require('autoprefixer');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

const config = require('./config.json');

let plugins = [];

plugins.push(new MiniCssExtractPlugin({
	filename: "../css/[name].css"
}));

plugins.push(new BrowserSyncPlugin({
	proxy: config.proxyURL
}));

plugins.push(new VueLoaderPlugin());

module.exports = (env, argv) => {
	let isDev = argv.mode !== 'production';

	return {
		"entry": config.entryPoints,
		"output": {
			"path": path.resolve(__dirname, 'assets/js'),
			"filename": '[name].js'
		},
		"devtool": isDev ? 'eval-source-map' : false,
		"module": {
			"rules": [
				{
					"test": /\.js$/i,
					"use": {
						"loader": "babel-loader",
						"options": {
							presets: ['@babel/preset-env']
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
										autoprefixer(),
										['tailwindcss']
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
					use: [
						{
							loader: 'file-loader',
							options: {
								outputPath: '../fonts',
							},
						},
					],
				},
				{
					test: /\.(png|je?pg|gif)$/i,
					use: [
						{
							loader: 'url-loader',
							options: {
								limit: 8192, // 8KB
								outputPath: '../images',
							},
						},
					],
				},
				{
					test: /\.svg$/i,
					use: [
						{
							loader: 'url-loader',
							options: {
								limit: 10240, // 10KB
								outputPath: '../images',
								generator: (content) => svgToMiniDataURI(content.toString()),
							},
						},
					],
				}
			]
		},
		optimization: {
			minimizer: [
				new TerserPlugin(),
				new OptimizeCSSAssetsPlugin()
			],
		},
		resolve: {
			alias: {
				'vue$': 'vue/dist/vue.esm.js',
				'@': path.resolve('./assets/src/'),
			},
			modules: [
				path.resolve('./node_modules'),
				path.resolve(path.join(__dirname, 'assets/src/')),
				path.resolve(path.join(__dirname, 'assets/src/shapla')),
			],
			extensions: ['*', '.js', '.vue', '.json']
		},
		"plugins": plugins
	}
}
