var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    less = require('gulp-less'),
    cssBase64 = require('gulp-css-base64'),
    imagemin = require('gulp-imagemin'),
    sourcemaps = require('gulp-sourcemaps'),
    rev = require('gulp-rev'),
    del = require('del')
    ;

// Prefix assets with their dir, so gulp.src works
var assets = require('./assets.json');
assets.css.main         = assets.css.main.map(function(val) { return 'public/assets/css/' + val; });
assets.css.bower        = assets.css.bower.map(function(val) { return 'public/bower_components/' + val; });
assets.javascript.main  = assets.javascript.main.map(function(val) { return 'public/assets/js/' + val; });
assets.javascript.bower = assets.javascript.bower.map(function(val) { return 'public/bower_components/' + val; });

// Run every once in a while to compress image assets
gulp.task('imagemin', function () {
    // Imagemin
    gulp.src('public/assets/img/**/*.{png,gif,jpg}')
        .pipe(imagemin())
        .pipe(gulp.dest('public/assets/img/'));
});

gulp.task('delete_build', function() {
    del(['public/build']);
});

gulp.task('scripts', function() {
    gulp.src(assets.javascript.main)
        .pipe(uglify())
        .pipe(concat('scripts.js'))
        // revision
        .pipe(rev())
        .pipe(gulp.dest('public/build'))
        .pipe(rev.manifest({path: 'manifest-scripts.json'}))
        .pipe(gulp.dest('public/build'))
    ;
});

gulp.task('bower_scripts', function() {
    gulp.src(assets.javascript.bower)
        .pipe(uglify())
        .pipe(concat('bower.js'))
        // revision
        .pipe(rev())
        .pipe(gulp.dest('public/build'))
        .pipe(rev.manifest({path: 'manifest-bower.json'}))
        .pipe(gulp.dest('public/build'))
    ;
});

gulp.task('styles', function() {
    gulp.src(assets.css.main)
        .pipe(sourcemaps.init())
        .pipe(less())
        .pipe(cssBase64())
        .pipe(concat('styles.css'))
        .pipe(sourcemaps.write())
        // revision
        .pipe(rev())
        .pipe(gulp.dest('public/build'))
        .pipe(rev.manifest({path: 'manifest-styles.json'}))
        .pipe(gulp.dest('public/build'))
    ;
});

gulp.task('bower_styles', function() {
    gulp.src(assets.css.bower)
        .pipe(sourcemaps.init())
        .pipe(cssBase64())
        .pipe(concat('bower.css'))
        .pipe(sourcemaps.write())
        // revision
        .pipe(rev())
        .pipe(gulp.dest('public/build'))
        .pipe(rev.manifest({path: 'manifest-bower-styles.json'}))
        .pipe(gulp.dest('public/build'))
    ;
    // Copy fonts
    gulp.src('public/bower_components/font-awesome/fonts/*')
        .pipe(gulp.dest('public/fonts'));
});

// Run by running 'gulp' with no params
gulp.task('default', ['delete_build', 'scripts', 'bower_scripts', 'styles', 'bower_styles']);