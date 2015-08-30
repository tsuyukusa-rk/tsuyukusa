module.exports = function(grunt){

  // load-grunt-tasksのプラグインで、loadNpmTasksを
  // 省略できる
  require('load-grunt-tasks')(grunt, {
    config: 'package.json',
    scope: 'devDependencies'
  });

  // タスク実行にかかった時間をコマンドに表示
  require('time-grunt')(grunt);

  // パスを定義
  var pass = {
    assets: 'assets/', // 編集用ディレクトリ
    dist: 'dist/' // 公開用ディレクトリ
  };

  //ここに実行内容を記述
  grunt.initConfig({

    // 必要に応じて、指定のフォルダを削除する
    clean: {
      dist: {
        src: pass.dist
      }
    },

    // ファイルをコンパイルし、指定フォルダにコピーする
    copy: {
      html: {
        expand: true,
        cwd: pass.assets,
        src: ['index.html', 'html/**'],
        dest: pass.dist
      },
      css: {
        expand: true,
        cwd: pass.assets,
        src: ['css/**/*.css'],
        dest: pass.dist
      },
      images: {
        expand: true,
        cwd: pass.assets,
        src: ['img/**'],
        dest: pass.dist
      },
      js: {
        expand: true,
        cwd: pass.assets,
        src: ['js/**/*.js', 'js/data/*.json','!js/common/*.js'],
        dest: pass.dist
      },
      blog: {
        expand: true,
        cwd: pass.assets,
        src: ['blog/**'],
        dest: pass.dist
      },
      contact: {
        expand: true,
        cwd: pass.assets,
        src: ['contact/**', '!contact/index.php'],
        dest: pass.dist
      },
      jplayer: {
        expand: true,
        cwd: pass.assets,
        src: ['jplayer/**'],
        dest: pass.dist
      },
      counter: {
        expand: true,
        cwd: pass.assets,
        src: ['count01.txt', 'playcount.php'],
        dest: pass.dist
      },
      file: {
        expand: true,
        cwd: '',
        src: ['.htaccess', 'httpd.conf'],
        dest: pass.dist
      }
    },

    // ファイルの結合
    concat: { //←concatじゃないとダメ
      js: { //←jsじゃなくても好きな名前でOK
        files: { //←ここはfilesじゃなきゃダメ
          'dist/js/common.js': [
            'assets/js/common/*.js'
            // 'js/common/common_ui.js',
            // 'js/common/intro.js'
          ]
        }
      }
    },

    // compassの設定
    compass: {
      dist: {
        options: {
          config: 'config.rb'
        }
      }
    },

    // Live Reload
    connect: {
      options: {
        hostname : 'localhost',
        base: pass.dist
      },
      livereload: {
          options : {
              port: 9000
          }
      }
    },

    // Watch
    watch: {
      // options
      options: {
        livereload: true,
        spawn: false
      },
      // html
      html: {
        cwd: pass.assets,
        files: '**/*.html',
        tasks: [],
        dest: pass.dist
      },
      // scss
      sass: {
        cwd: pass.assets,
        files: '**/*.scss',
        tasks: ['compass'],
        dest: pass.dist
      },
      // Java Script
      js: {
        cwd: pass.assets,
        files: '**/*.js',
        tasks: ['concat'],
        dest: pass.dist
      }
    },

    // gruntコマンド実行時にページをブラウザで開く
    open: {
      server: {
        path: 'http://localhost:<%= connect.livereload.options.port %>'
      }
    }

  });

  //タスクの登録
  grunt.registerTask('server', ['concat', 'compass', 'copy', 'connect', 'open', 'watch']);

}