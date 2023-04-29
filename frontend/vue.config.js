module.exports = {

    // output built static files to Laravel's public dir.
    // note the "build" script in package.json needs to be modified as well.
    outputDir: '../public',

    // modify the location of the generated HTML file.
    // make sure to do this only in production.
    indexPath: '../resources/views/spa.blade.php',

    // modifications to the CSS config
    css: {
        loaderOptions: {
            // this makes the global variables available to all Vue components
            sass: {
                prependData: '@import "@/stylesheets/abstracts/_variables.scss";'
                    + '@import "@/stylesheets/abstracts/_mixins.scss";',
            },
        },
    },
    configureWebpack: {
        devtool: 'source-map',
    },
};
