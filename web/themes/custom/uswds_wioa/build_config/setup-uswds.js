const gulp = require('gulp');
const log = require('fancy-log');
const c = require('ansi-colors');

gulp.task('copy-uswds-docs', () => {
  log(c.bold.bgMagenta('Copying docs from uswds'));

  return gulp.src(['./node_modules/uswds/src/*.md']).pipe(gulp.dest('.'));
});

gulp.task('copy-uswds-fonts', () => {
  log(c.bold.bgMagenta('Copying fonts from uswds'));

  return gulp
    .src('./node_modules/uswds/src/fonts/**/*')
    .pipe(gulp.dest('fonts/uswds'));
});

gulp.task('copy-uswds-images', () => {
  log(c.bold.bgMagenta('Copying images from uswds'));

  return gulp
    .src('./node_modules/uswds/src/img/**/*')
    .pipe(gulp.dest('images'));
});

gulp.task('copy-uswds-scss', () => {
  log(c.bold.bgMagenta('Copying SCSS from uswds'));

  return gulp
    .src('./node_modules/uswds/src/stylesheets/**/*')
    .pipe(gulp.dest('sass/vendor/uswds'));
});

gulp.task('copy-uswds-js', () => {
  log(c.bold.bgMagenta('Copying JS from uswds'));

  return gulp
    .src('./node_modules/uswds/dist/js/**/*')
    .pipe(gulp.dest('js/vendor/uswds'));
});
