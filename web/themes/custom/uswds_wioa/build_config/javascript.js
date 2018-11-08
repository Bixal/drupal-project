const gulp        = require('gulp')
const eslint      = require('gulp-eslint')
const notify      = require('gulp-notify')
const browserSync = require('browser-sync')
const plumber     = require('gulp-plumber')
const sourcemaps  = require('gulp-sourcemaps')
const reload      = browserSync.reload
const babel       = require('gulp-babel')

gulp.task('eslint', () => {
  return gulp.src('js/*.js')
    .pipe(eslint({
      parser: 'babel-eslint',
      rules: {
        'no-mutable-exports': 0,
      },
      globals: [
        'jQuery',
        '$',
      ],
      envs: [
        'browser',
      ]
    }))
    .pipe(eslint.format())
})

gulp.task('scripts', () => {
  return gulp.src('js/*.js')
    .pipe(plumber({ errorHandler: function(err) {
      notify.onError({
        title: `Gulp error in ${err.plugin}`,
        message: err.toString()
      })
    }}))
    .pipe(babel({
      presets: ['env'],
    }))
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('assets/js'))
    .pipe(reload({stream: true}))
})