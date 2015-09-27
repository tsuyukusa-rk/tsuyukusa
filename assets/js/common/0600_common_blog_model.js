;(function(APP){
// ブログのモデルを記述

    APP.blog.model.data = Backbone.Model.extend({

        // 初期化の関数
        // 必ず呼び出されたときに実行される
        initialize: function() {
            // alert('初期化');
        }

    });

    var bolgData = new APP.blog.model.data();

// /*
// * サンプルコード
// */
// // プロトタイプチェインの例
// var objA = {
// 	name: 'yome',
// 	say: function() {
// 		alert('I love' + this.name);
// 	}
// };

// var objB = {
// 	name: 'Nikole'
// };
// objB.__proto__ = objA;

// var objC = {};
// objC.__proto__ = objB;

// objC.say();

// // プロトタイプの例

// // コンストラクタ関数の定義
// var person = function(name) {
// 	this.name = name;
// };
// // prototypeの拡張
// person.prototype.sayHello = function() {
// 	alert('Hello' + this.name);
// }
// // 起動
// var person = new person('Nicole');

}(TS));