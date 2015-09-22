;(function(APP){

	// ie8以下でindexofを使えるようにする
	if (!Array.indexOf) {
		Array.prototype.indexOf = function(o) {
			for (var i in this) {
				if (this[i] == o) {
				return i;
				}
			}
				return -1;
		}
	}

// ##### 変数 ################################################

	var $pege;
	var $canvas;

// ##### 処理 ################################################

	/*
	** 初期処理
	** @parma
	*/
	APP.intro.init = function() {

		$pege = $('.pageIntro');
		$canvas = $('#introCanvas')[0];

		// ウィンドウの高さを取得し、イントロページに設定
		var winHeight = $(window).height();
		$pege.height(winHeight);
		$pege.find('div').css({'paddingTop': winHeight / 2 - 101});

		// ロゴの表示
		$pege.find('img').fadeIn();
		$pege.find('.goTop').fadeIn();
		$('#introCanvas').fadeIn();

		$pege.find('.goTop').on('click', goToTop);

		// 設定された秒数経過後に処理を実行
		var introFadeout = setTimeout(goToTop, 2500);

		// トップページを表示する処理
		function goToTop() {

			clearTimeout(introFadeout);
			$pege.find('div').fadeOut(1000, function() {
				$pege.hide();
				// effectchain.jsの呼び出し
				$("body").effectChain({ target: "div" });
			});
		};

	},

	/*
	** キャンバスの設定
	** @parma
	*/
	canvasSetting = {
		x: 10,
		y: 20,
		lineX: 5,
		lineY: 10,
		lineXX: 5,
		lineYY: 10
	},

	/*
	** キャンバスのアニメーション設定
	** @parma
	*/
	APP.intro.loop = function() {

		var canSet = canvasSetting;
		canSet.x += 1;
		canSet.y += 0.5;
		canSet.lineX += 1;
		canSet.lineY += 1;
		canSet.lineXX += 1.1;
		canSet.lineYY += 1.1;

		// 一定以上の数値になった時のリセット処理
		if(canSet.lineX > 110) {
			canSet.lineX = 0;
			canSet.lineY = 0;
			canSet.lineXX = 0;
			canSet.lineYY = 0;
		}

		// 図形描画
		APP.intro.canvasDraw(canSet.x, canSet.y, canSet.lineX, canSet.lineY, canSet.lineXX, canSet.lineYY);

		// ループのタイマーを設定
		var timer = setTimeout(APP.intro.loop, 20);
	},

	/*
	** 図形描画設定処理
	** @parma loopX, loopY
	*/
	APP.intro.canvasDraw = function(loopX, loopY, lineX, lineY, lineXX, lineYY) {

		if($canvas.getContext) {
		// キャンバスタグが存在している場合

			var context = $canvas.getContext('2d');
			var height = $canvas.height; // キャンバスの高さを取得
			var width = $canvas.width; // キャンバスの横幅を取得

			// キャンバスをいったんクリアにする
			context.clearRect(0,0,300,200);

			// 影をつける
			context.shadowBlur = 8;
			context.shadowColor = '#aaaaaa';

			// 線を描画する処理
			context.beginPath(); // パス設定開始
			context.strokeStyle = '#263260'; // 線の色を指定する
			context.moveTo(70, 0); // 開始位置を指定
			context.lineTo(135, 200); // 終了位置を指定
			context.closePath(); // パス設定を終了
			context.stroke(); // 線を描画

			// 図形を描画する処理
			context.strokeStyle = '#263260'; // 線の色を指定する
			context.strokeRect(40,10,200, loopY); // 枠線を描画
			context.fillRect(50,20,loopX, loopY); // 図形を描画
			context.clearRect(65,30,150,40); // キャンバス上をクリアする

			// 円を描く処理
			context.beginPath();
			context.arc(0, 0, 35, 0, Math.PI*2, true);
			context.closePath(); // パス設定を終了
			context.fill(); // 線を描画

		}
	};

// ##### IEブラウザバージョン判別 ################################################

	var userAgent = window.navigator.userAgent.toLowerCase();
	var appVersion = window.navigator.appVersion.toLowerCase();

	if (userAgent.indexOf('msie') != -1) {
	//IE全般
		if (appVersion.indexOf("msie 6.") != -1) {
		//IE6
			init();
			return false;

		} else if (appVersion.indexOf("msie 7.") != -1) {
		//IE7
			init();
			return false;

		} else if (appVersion.indexOf("msie 8.") != -1) {
		//IE8
			init();
			return false;

		} else if (appVersion.indexOf("msie 9.") != -1) {
		//IE9
		} else if (appVersion.indexOf("msie 10.") != -1) {
		//IE10
		}
	}

}(TS));