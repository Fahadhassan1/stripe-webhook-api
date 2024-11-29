const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');
require('laravel-mix-purgecss');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
  .sass('resources/scss/app.scss', 'public/css')
  .options({
    postCss: [
      tailwindcss('tailwind.config.js'),
    ],
    processCssUrls: false,
  })
  .purgeCss({
    enabled: mix.inProduction(),
    folders: [
      'resources/js/components',
      'resources/views',
    ],
    extensions: ['vue', 'css', 'scss','blade.php'],
    safelist: {
      standard: [
        /multiselect/,
        /daterangepicker/,
        /\[.*\]/,
        /!.*/,
      ],
    },
  })
  .webpackConfig({
    output: {
      chunkFilename: 'js/components/[name].js?id=[chunkhash]',
    },
    resolve: {
      fallback: {
        crypto: require.resolve('crypto-browserify'),
        stream: require.resolve('stream-browserify'),
      },
    },
  })
  .browserSync({
    proxy: 'localhost'
   })
  .vue();

if (mix.inProduction()) {
  mix.version();
}
