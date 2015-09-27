;(function(APP){
// ブログのviewを記述

    /*
     * ブログメインコンテンツ吐き出し用のview
     */
    APP.blog.view.mainContents = Backbone.View.extend({

        // 対象セレクターを定義
        el: '.blogMainContentsArea',

        // データの格納先
        item: '',

        // データの数を定義
        dataLength: '',

        // テンプレートの定義
        template: _.template('<li>'
            + '<dl>'
                + '<dt><%= title %><br><span><%= uploadDate %></span></dt>'
                + '<% _.each(text, function(el, i) { %>'
                    + '<dd><%= el %></dd>'
                + '<% }); %>'
            + '</dl>'
        + '</li>'),

        // 呼び出されたときに必ず呼ばれる初期処理
        initialize: function(blogData) {
            // alert('viewの初期化');
            // 持ってきたデータを格納
            this.item = blogData;
            this.dataLength = this.item.length;
            // renderの呼び出し
            for(i = 0; this.dataLength > i; i++) {
                var count = this.dataLength - i;
                this.render(count, this.$el, this.template);
            }
        },

        // 核となる関数
        render: function(count, $el, template) {
            _.each(this.item, function(obj, i) {
                if(obj.index == count) {
                // もしindexがcountと等しい場合に、HTMLにブログ文書を追加
                    $el.append(template(obj));
                }
            });
        }

    }),

    /*
     * 最近書いた記事掃き出し用のview
     */
     APP.blog.view.recentArticle = Backbone.View.extend({

        // 対象セレクターを定義
        el: '.recentArticleArea dl',

        // データの格納先
        item: '',

        // データの数を定義
        dataLength: '',

        // テンプレートの定義
        template: _.template('<dd><a href="#">・<%= title %></a></dd>'),

        // 呼び出されたときに必ず呼ばれる初期処理
        initialize: function(blogData) {
            // 持ってきたデータを格納
            this.item = blogData;
            this.dataLength = this.item.length;
            // render呼び出し
            for(i = 0; i < 4; i++) {
                var count = this.dataLength - i;
                // 最大四つまで、最近の記事を表出
                this.render(count, this.$el, this.template);
            }
        },

        render: function(count, $el, template) {
            _.each(this.item, function(arr, i) {
                if(arr.index == count) {
                    $el.append(template(arr));
                }
            });
        }

     });

    // サンプルデータを使い呼び出す
    $(function() {
        // ブログメインコンテンツ吐き出し
        new APP.blog.view.mainContents(sampleData);
        // 最近書いた記事掃き出し
        new APP.blog.view.recentArticle(sampleData);
    });

    // テスト用データ
    var sampleData = [
        {
            "uploadDate": "2015/09/23",
            "category": "live",
            "title": "ライブについてライブについてライブについて",
            "text": [
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキスト",
                    "<img src=\"/img/blog/20150923_01.jpg\" alt=\"\">",
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキスト"
            ],
            "imgSrc1": "/img/blog/20150923_01.jpg",
            "index": 1
        },
        {
            "uploadDate": "2015/09/24",
            "category": "songs",
            "title": "曲作り・宅録曲作り・宅録",
            "text": [
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプル",
                    "<img src=\"/img/blog/20150924_01.jpg\" alt=\"\">",
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキスト"
            ],
            "imgSrc1": "/img/blog/20150923_01.jpg",
            "index": 2
        },
        {
            "uploadDate": "2015/09/25",
            "category": "bookReviews",
            "title": "最近読んだ本最近読んだ本",
            "text": [
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキスト",
                    "<img src=\"/img/blog/20150923_01.jpg\" alt=\"\">",
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキスト"
            ],
            "imgSrc1": "/img/blog/20150923_01.jpg",
            "index": 3
        },
        {
            "uploadDate": "2015/09/26",
            "category": "eventsOfOneDay",
            "title": "ある日の出来事ある日の出来事",
            "text": [
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプル",
                    "<img src=\"/img/blog/20150924_01.jpg\" alt=\"\">",
                    "サンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキストサンプルテキスト"
            ],
            "imgSrc1": "/img/blog/20150923_01.jpg",
            "index": 4
        }
    ];

}(TS));