const gulp = require('gulp');
const rollup = require('rollup');
const babel = require('rollup-plugin-babel');
const commonjs = require('rollup-plugin-commonjs');
const resolve = require('rollup-plugin-node-resolve');
const terser = require('rollup-plugin-terser');
const upath = require('upath');

const buildPlugins = (minify) => {
    return [
        resolve({
            jail: upath.resolve('./node_modules'),
        }),
        commonjs({
            include: './node_modules/**',
        }),
        babel({
            babelrc: false,
            exclude: './node_modules/**',
            presets: [
                [
                    '@babel/preset-env',
                    {
                        modules: 'auto',
                        forceAllTransforms: false,
                        useBuiltIns: 'usage',
                        corejs: '3.8',
                    },
                ],
            ],
            plugins: [
                '@babel/plugin-proposal-class-properties',
                '@babel/plugin-proposal-private-methods',
                '@babel/plugin-syntax-class-properties',
            ],
        }),
        minify && terser.terser(),
    ];
};

gulp.task('js', () => {
    return rollup.rollup({
        input: './assets/js/websocket.js',
        plugins: buildPlugins(false),
        treeshake: false,
    }).then(bundle => {
        return bundle.write({
            file: './public/js/websocket.js',
            format: 'es',
            sourcemap: false,
        });
    });
});

gulp.task('min-js', () => {
    return rollup.rollup({
        input: './assets/js/websocket.js',
        plugins: buildPlugins(true),
        treeshake: false,
    }).then(bundle => {
        return bundle.write({
            file: './public/js/websocket.min.js',
            format: 'es',
            sourcemap: false,
        });
    });
});

gulp.task('default', gulp.series(['js', 'min-js']));
