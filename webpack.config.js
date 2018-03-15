module.exports = {
	devtool: 'source-map',
	entry: './src/default.js',
	resolve: {
		alias: {
			vue: 'vue/dist/vue.js'
		}
	},
	output : {
		path: `${__dirname}/js`,
		filename : 'user_ldap.bundle.js'
	},
	module: {
		rules: [{
			test: /\.js?$/,
			exclude: [/node_modules/],
			include: [/src/],
			use: 'babel-loader',
		}, {
			test: /\.less?$/,
			use: [
				'style-loader',
				'css-loader',
				'less-loader'
			]
		}, {
			test: /\.vue$/,
			use: 'vue-loader'
		}]
	}
}
