const gulp        = require('gulp')
const sass        = require('gulp-sass')
const eslint      = require('gulp-eslint')
const scsslint    = require('gulp-sass-lint')
const  prefix     = require('autoprefixer')
const notify      = require('gulp-notify')
const postcss     = require('gulp-postcss')
const browserSync = require('browser-sync')
const sassGlob    = require('gulp-sass-glob')
const plumber     = require('gulp-plumber')
const sourcemaps  = require('gulp-sourcemaps')
const cssnano     = require('gulp-cssnano')
const reload      = browserSync.reload

const paths = {
  styles: [
    'sass/**/*.scss'
  ],
  scripts: [
    'js/*.js'
  ],
  images: {
    src: './images/**/*',
    svg: './images/icons/*.svg'
  }
}

gulp.task('sass', () => {
  return gulp.src('sass/styles.scss')
    .pipe(plumber({ errorHandler: function(err) {
      notify.onError({
        title: `Gulp error in ${err.plugin}`,
        message:  err.toString()
      })(err)
    }}))
    .pipe(sourcemaps.init())
    .pipe(sassGlob())
    .pipe(sass({outputStyle: 'compressed'}))
    .pipe(cssnano({zindex: false}))
    .pipe(postcss([
      prefix({
        browsers: ['last 3 versions'],
        cascade: false })
      ]))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('assets/dist/css'))
    .pipe(reload({stream:true}))
})

gulp.task('eslint', () => {
  gulp.src(paths.scripts)
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
  return gulp.src(paths.scripts)
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

gulp.task('browser-sync', () => {
  browserSync({
    proxy: {
      // Your local projects URL.
      target: "http://wioa.docker.localhost:8000"
    }
  })
})

gulp.task('watch', () => {
  gulp.watch(paths.styles, gulp.series('sass')).on('change', reload)
  gulp.watch(paths.scripts, gulp.series('scripts', 'eslint')).on('change', reload)
});

gulp.task('default', gulp.parallel('sass', 'browser-sync', 'watch'))