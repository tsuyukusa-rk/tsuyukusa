$(function(){

// ##### 処理 ###########################################

	// トップの表示設定
	var setting = function(){

		/**
		 **	メインビジュアル用スライダー
		 */
		var autoSlide = setInterval(mainSlider, 6000);

		function mainSlider() {

			var $slide = $(".mainSlider");//スライダー部分のセレクター
			var $slideLiActive = $slide.find(".active");//表示されているものがactive
			var $slideLiActiveNum = $slide.find("li").index($slideLiActive);//activeが何番目か

			//もしアクティブが一番目のliであれば
			if($slideLiActiveNum == 1){
				//表示切替処理
				$slideLiActive.removeClass("active").fadeOut(1500, function() {
					$(".mainSlider li:first-child").addClass("active");
				});

			//アクティブが二番目以降についてるとき
			} else {
				//表示切替処理
				$slideLiActive.removeClass("active").fadeIn(1500, function() {
					$slideLiActive.next("li").addClass("active").fadeIn(1500);
				});
			}

		};

		/**
		 **	アコーディオン処理
		 */
		//クリックしたときに
		$(".accordion h3").on("click", function(){
			//PC表示の場合
			if ($(window).width() >= 640) {
				$("ul").removeClass("accordion");//アコーディオン動かないようにする

			//SP表示の場合
			} else if ($(window).width() <= 640) {
				//アコーディオンを動くようにする
				$("ul").addClass("accordion");
				$(".accordion h3").next("dl").addClass("displayNone");
				$(this).next("dl").slideToggle();
				$(this).toggleClass("open");
			}
		});

		/**
		 **	リサイズ制御
		 */
		$(window).resize(function(){

			//PC表示の場合
			if ($(this).width() >= 640) {
				$("ul").removeClass("accordion");
				$("dl").removeClass("displayNone").show();

			//SP表示の場合
			} else if ($(this).width() <= 640) {
				$(".accordion h3").next("dl").addClass("displayNone").hide();
				$("ul").addClass("accordion");
			}

		});

		/**
		 **	トップへ戻るボタン
		 */
		//スクロール位置取得処理
		$(window).scroll(function(){
			var $this = $(this);
			var targetShow = $this.scrollTop();//スクロール位置

			if(targetShow >= 100){//100以下になったら表示
				$(".returnTop").fadeIn();
			} else {//101以上の時には、非表示
				$(".returnTop").fadeOut();
			}
		});

		//トップへ戻る処理
		$(".returnTop").click(function(){
			$('body,html').animate({scrollTop:0}, 1000, "easeOutQuad");	
			return false;
		});

		/**
		 **	カレンダー用モーダル
		 */
		// クリックしたあとの処理
		$('.jsShowModal').on('click',function(){
			var $this = $(this);
			var thisTop = $this.offset().top;
			var windowWidth = $(window).width();
			var modal = $('.jsclenderModal');//カレンダー
			var overlay = $('.jsclenderModalOverlay');//オーバーレイ

			overlay.fadeIn();// オーバーレイ表示
			modal.css({top:thisTop - 220,left:windowWidth / 2 - 150}).fadeIn(300);//モーダル表示

			// 閉じるボタンをクリックした時の処理
			$('.jsclenderModalClose').on('click', function(){
				modal.fadeOut(300);
				overlay.fadeOut(200);
			});

			overlay.on('click', function(){
				modal.fadeOut(300);
				overlay.fadeOut(200);
			});

			return false;
		});

	};

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

// #### 起動 ##################################################

	// トップの表示設定
	setting();

});