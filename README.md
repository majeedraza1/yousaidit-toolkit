# WordPress Plugin Starter

A WordPress [Vue.js](https://vuejs.org/) or [React](https://reactjs.org/) starter plugin with required toolbelts 😎

## 📦 What it ships with?

 - Pre-configured webpack 5 configuration
   - Babel loader, CSS and SCSS loader
   - PostCss loader with Autoprefixer
   - Uglify JS for production
   - Separate `frontend.js` and `admin.js`
   - Extracted CSS/SCSS to separate `frontend.css` and `admin.css` files.
   - Auto reloading with Browser with **BrowserSync** *([config](config.json))*
 - Modern PHP codebase with [namespace](http://php.net/manual/en/language.namespaces.php) and PSR-4 autoloading.


## 🚚 Running

1. Download this repository in your plugins folder
1. Activate the plugin

## 👨‍💻 Post Installation

1. Change plugin name form `Yousaidit Toolkit` to your desired name.
1. The name of the plugin class is `StackonetToolkit`, change the class name with your desired class name.
1. Replace the PHP namespace `Yousaidit` with your desired name in `composer.json` file.
1. Replace the PHP namespace `Yousaidit` with your desired name for all files in `includes` directory.
1. Replace `YOUSAIDIT_TOOLKIT` reference in all files in case sensitive mode.
1. Replace `yousaidit-toolkit` reference in all files in case sensitive mode.
1. Replace `yousaidit_toolkit` reference in all files in case sensitive mode.
1. Update `proxyURL` in `config.json` file.
1. Run `npm install` to install javaScript dependency.
1. Run `composer install` to install PHP dependency if any (optional).
1. To start developing, run `npm run dev` 🤘
1. For production build, run `npm run build` 👍

In PhpStorm, you can select plugin folder and press `Ctrl + Shift + r` to find and replace easily.

## About

Made by [Sayful Islam](https://sayfulislam.com) for [Yousaidit Services (Pvt.) Ltd.](https://www.stackonet.com)

*Found anything that can be improved? You are welcome to contribute.*
