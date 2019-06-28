const gulp = require('gulp');
const babel = require('gulp-babel');
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');
const wrap = require('gulp-wrap');

gulp.task('js', () => {
    return gulp.src('js/websocket.js')
        .pipe(babel({
            presets: ['@babel/preset-env']
        }))
        .pipe(wrap({src: 'js/websocket.template.js'}))
        .pipe(gulp.dest('public/js'))
        .pipe(rename({extname: '.min.js'}))
        .pipe(uglify())
        .pipe(gulp.dest('public/js'));
});

gulp.task('default', gulp.series(['js']));
