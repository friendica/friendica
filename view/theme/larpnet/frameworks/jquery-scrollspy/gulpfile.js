/* global require */

var gulp = require('gulp');
var eslint = require('gulp-eslint');
var gulpIf = require('gulp-if');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify');

// Assets for the project
var Assets = {
    main: './jquery-scrollspy.js',
    minified: './jquery-scrollspy.min.js',
    package: './package.json',
    readme: './README.md',
    source: './',
};

// See the uglify documentation for more details
var _uglifySettings = {
    compress: {
        comparisons: true,
        conditionals: true,
        /* jscs: disable */
        dead_code: true,
        drop_console: true,
        /* jscs: enable */
        unsafe: true,
        unused: true,
    },
};

// Check the main js file(s) meets the following standards outlined in .eslintrc
gulp.task('eslint', function esLintTask() {
    // Has ESLint fixed the file contents?
    function isFixed(file) {
        return file.eslint !== undefined && file.eslint !== null && file.eslint.fixed;
    }

    return gulp.src(Assets.main)
        .pipe(eslint({
            fix: true,
            useEslintrc: '.eslintrc',
        }))
        .pipe(eslint.format())
        .pipe(gulpIf(isFixed, gulp.dest(Assets.source)));
});

// Uglify aka minify the main file
gulp.task('uglify', function uglifyTask() {
    return gulp.src(Assets.main)
        .pipe(uglify(_uglifySettings))
        .pipe(rename(Assets.minified))
        .pipe(gulp.dest(Assets.source));
});

// Watch for changes to the main file
gulp.task('watch', function watchTask() {
    gulp.watch(Assets.main, ['eslint', 'uglify']);
});

// Register the default task
gulp.task('default', ['eslint', 'uglify']);

// 'gulp eslint' to check the syntax of the main js file(s)
// 'gulp uglify' to uglify the main file
