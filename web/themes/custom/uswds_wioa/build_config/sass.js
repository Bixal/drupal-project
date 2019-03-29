const gulp = require('gulp');
const sass = require('gulp-sass');
const scsslint = require('gulp-sass-lint');
const prefix = require('autoprefixer');
const notify = require('gulp-notify');
const postcss = require('gulp-postcss');
const browserSync = require('browser-sync');
const sassGlob = require('gulp-sass-glob');
const plumber = require('gulp-plumber');
const sourcemaps = require('gulp-sourcemaps');
const cssnano = require('gulp-cssnano');
const reload = browserSync.reload;

gulp.task('sass', () => {
  return gulp
    .src('sass/styles.scss')
    .pipe(
      plumber({
        errorHandler: function(err) {
          notify.onError({
            title: `Gulp error in ${err.plugin}`,
            message: err.toString()
          })(err);
        }
      })
    )
    .pipe(sourcemaps.init())
    .pipe(sassGlob())
    .pipe(sass({ outputStyle: 'compressed' }))
    .pipe(cssnano({ zindex: false }))
    .pipe(
      postcss([
        prefix({
          browsers: ['last 3 versions'],
          cascade: false
        })
      ])
    )
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('css'))
    .pipe(reload({ stream: true }));
});

gulp.task('scsslint', () => {
  return gulp
    .src('sass/**/*.scss')
    .pipe(
      scsslint({
        options: {
          configFile: '.sass-lint.yml'
        }
      })
    )
    .pipe(scsslint.format());
});

gulp.task('styles', gulp.series('sass', 'scsslint'));
