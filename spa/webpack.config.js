const Encore = require('@symfony/webpack-encore');
const HtmlWebpackPlugin = require('html-webpack-plugin');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  .setOutputPath('public/')
  .setPublicPath('/')
  .cleanupOutputBeforeBuild()
  .addEntry('app', './src/app.js')
  .enablePreactPreset()
  .enableSassLoader()
  .enableSingleRuntimeChunk()
  .addPlugin(new HtmlWebpackPlugin({ template: 'src/index.ejs', alwaysWriteToDisk: true }));

module.exports = Encore.getWebpackConfig();
