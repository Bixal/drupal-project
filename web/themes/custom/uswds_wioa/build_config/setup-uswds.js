const gulp  = require('gulp')
const log = require('fancy-log')
const c = require('ansi-colors')

gulp.task('copy-uswds-docs', () => {
  log(
    c.bold.bgMagenta('Copying docs from uswds')
  )

  return gulp.src(['./node_modules/uswds/src/*.md'])
    .pipe(gulp.dest('assets/dist'))
})

gulp.task('copy-uswds-fonts', () => {
  log(
    c.bold.bgMagenta('Copying fonts from uswds')
  )

  return gulp.src('./node_modules/uswds/src/fonts/**/*')
    .pipe(gulp.dest('assets/dist/fonts'))
})

gulp.task('copy-uswds-images', () => {
  log(
    c.bold.bgMagenta('Copying images from uswds')
  )

  return gulp.src('./node_modules/uswds/src/img/**/*')
    .pipe(gulp.dest('assets/dist/img'))
})

gulp.task('copy-uswds-scss', () => {
  log(
    c.bold.bgMagenta('Copying SCSS from uswds')
  )

  return gulp.src('./node_modules/uswds/src/stylesheets/**/*')
    .pipe(gulp.dest('assets/dist/scss'))
})

gulp.task('copy-uswds-js', () => {
  log(
    c.bold.bgMagenta('Copying JS from uswds')
  )

  return gulp.src('./node_modules/uswds/src/js/**/*')
    .pipe(gulp.dest('assets/dist/js'))
})
