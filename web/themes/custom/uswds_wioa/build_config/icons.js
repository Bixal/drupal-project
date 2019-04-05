const gulp = require('gulp');
const imagemin = require('gulp-imagemin');
const iconfont = require('gulp-iconfont');
const iconfontCSS = require('gulp-iconfont-css');

const runTimestamp = Math.round(Date.now() / 1000);
const fontName = 'wioa-icons';

gulp.task('optimize-images', () => {
  return gulp
    .src('./images/**/*', { base: '.' })
    .pipe(imagemin())
    .pipe(gulp.dest('.'));
});

gulp.task('iconfont', () => {
  return gulp
    .src(`./images/${fontName}/*.svg`)
    .pipe(
      iconfontCSS({
        fontName: fontName,
        path: 'sass/templates/icons.scss',
        targetPath: '../../sass/global/_icons.scss',
        fontPath: `../fonts/${fontName}/`,
        cacheBuster: runTimestamp
      })
    )
    .pipe(
      iconfont({
        fontName: fontName,
        // Remove woff2 if you get an ext error on compile
        formats: ['svg', 'ttf', 'eot', 'woff', 'woff2'],
        normalize: true,
        fontHeight: 1001,
        prependUnicode: true,
        timestamp: runTimestamp
      })
    )
    .pipe(gulp.dest(`./fonts/${fontName}`));
});
