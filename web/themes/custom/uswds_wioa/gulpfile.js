require('./build_config/build');

const gulp = require('gulp');
const browserSync = require('browser-sync');
const reload = browserSync.reload;

const project_url = 'http://wioa.docker.localhost:8000';

const paths = {
  styles: ['sass/**/*.scss'],
  scripts: ['js/*.js'],
  images: {
    src: './images/**/*',
    svg: './images/icons/*.svg'
  }
};

gulp.task('browser-sync', () => {
  browserSync({
    proxy: {
      // Your local projects URL.
      target: project_url
    }
  });
});

gulp.task(
  'setup-uswds',
  gulp.parallel(
    'copy-uswds-images',
    'copy-uswds-js',
    'copy-uswds-fonts',
    'copy-uswds-scss'
  )
);

gulp.task('icons', gulp.series('optimize-images', 'iconfont', 'styles'));

gulp.task(
  'build',
  gulp.series('setup-uswds', 'icons', 'styles', 'scripts', 'eslint')
);

gulp.task('watch', () => {
  gulp.watch(paths.styles, gulp.series('styles')).on('change', reload);
  gulp
    .watch(paths.scripts, gulp.series('scripts', 'eslint'))
    .on('change', reload);
});

gulp.task('default', gulp.parallel('styles', 'browser-sync', 'watch'));
