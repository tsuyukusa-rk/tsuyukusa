// ロード後に実行
$(function() {

	// APIを呼び出す
	TS.api.apiCall();

	// 初期処理起動
	TS.intro.init();

	// 図形描画開始
	TS.intro.loop();

	// トップの表示設定
	TS.ui.setting();

	/**
	 **	jplayerの起動
	 */
	function playerActive(target) {

		// ロード時とクリックでの選択時とで、分岐
		if(target !== 'firstPlayer') {
			var that = $(this);
			var play = 'play';
		} else {
			var that = $('.firstPlayer a');
			var play = '';
		}

		// クリアしたのち、選択されている曲にクラスを付与
		$('.cd ul li a').removeClass('activeSong');
		that.addClass('activeSong');

		// 一旦、プレイヤーを初期化
		$("#audio_player").jPlayer( "clearMedia" );
		$("#audio_player").jPlayer("destroy");

		// プレイヤーに情報をセット
		$("#audio_player").jPlayer({
			ready: function () {
				$(this).jPlayer("setMedia", {
					mp3:that.attr('data-src')
				}).jPlayer(play);
			},
			volume: 0.5,
			swfPath: "../../js",
			supplied: "mp3"
		});
		return false;
	};

	// 曲選択時の起動
	$('.cd ul li a').on('click', playerActive);

	$('#jp_container_1 a.jp-stop').click(function(){
		return false;
	});

	// 初期表示の際、一番上のものを選択状態にする呼び出し
	playerActive('firstPlayer');

	/*
	** 再生ボタンクリック回数を記録
	*/
	var randomNum = Math.floor(Math.random()*10000);
	var uri = 'count01.txt?r=' + randomNum;
	$('.counter').load(uri);

	function countOutput(e) {
		var randomNum = Math.floor(Math.random()*10000);
		$('.counter').load(uri);
	};

	$('.jp-play, .contentsBottomLeft .cd ul li a').on('click', function(e){

		$.post('playcount.php', {'param1': 'count01.txt'}, countOutput);
		$('.counter').load('count01.txt');
	});

});