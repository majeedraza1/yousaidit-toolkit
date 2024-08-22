const path = require('path');
const commonConfig = require('./.config/webpack.common.js');

const config = require('./config.json');

module.exports = (env, argv) => {
    const _config = commonConfig(env, argv);
    _config.entry = config.entryPoints;
    _config.output = {
        "path": path.resolve('./assets/js'),
        "filename": '[name].js'
    }
    _config.optimization.splitChunks = {
        cacheGroups: {
            commonVendors: {
                test: /[\\/]node_modules[\\/]/,
                name: 'vendors',
                chunks: 'all',
            }
        }
    }
    return _config;
}
