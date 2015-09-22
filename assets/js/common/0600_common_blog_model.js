;(function(APP){
// ブログのモデルを記述


/*
* サンプルコード
*/
// プロトタイプチェインの例
var objA = {
	name: 'yome',
	say: function() {
		alert('I love' + this.name);
	}
};

var objB = {
	name: 'Nikole'
};
objB.__proto__ = objA;

var objC = {};
objC.__proto__ = objB;

objC.say();

// プロトタイプの例

// コンストラクタ関数の定義
var person = function(name) {
	this.name = name;
};
// prototypeの拡張
person.prototype.sayHello = function() {
	alert('Hello' + this.name);
}
// 起動
var person = new person('Nicole');

}(TS));