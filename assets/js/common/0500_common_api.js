$(function(){

// ##### 処理 ###########################################

	/**
	 **	設定
	 */
	var setting = {
		url: '/js/data/schedule.json',
		data: ''
	},

	/**
	 **	スケジュールの埋め込み処理
	 ** @param setteng
	 */
	apiCall = function(setting){

		$.ajax({
			url: setting.url, //urlをセット
			cache: false // キャッシュをさせない

		}).done(function(data){
			// JSONが取得できたら、HTMLを組て立る 
			doBuild(data);

		}).fail(function(data){
			// JSONが取得できなければ、アラート 
			alert('error!!!');
			return false;

		});
	},

	/**
	 **	スケジュールの埋め込み処理
	 ** @param json
	 */
	doBuild = function(json){

		var scheduleArray = []; // トップページ表示用のHTML格納場所
		var scheduleArrayModal = []; // モーダル表示用のHTML格納場所

		// スケジュールの数だけ回す
		for(i=0; i < json.schedule.length; i++){

			if(i < 4){
			// スケジュールのデータが、３以下の場合はトップページへ
				scheduleArray.push('<dt><span>' + json.schedule[i].date + '<br><a href="' + json.schedule[i].url + '" target="_blank">' + json.schedule[i].place + '</a></span></dt><dd class="liveScheduleTxt">open&nbsp;' + json.schedule[i].time[0].open + '&nbsp;/&nbsp;start&nbsp;' + json.schedule[i].time[0].start + '<br>ticket&nbsp;' + json.schedule[i].ticket + '</dd>');

			} else {
			// スケジュールのデータが、４以上の場合はモーダルへ
				scheduleArrayModal.push('<dt><span>' + json.schedule[i].date + '<br><a href="' + json.schedule[i].url + '" target="_blank">' + json.schedule[i].place + '</a></span></dt><dd class="liveScheduleTxt">open&nbsp;' + json.schedule[i].time[0].open + '&nbsp;/&nbsp;start&nbsp;' + json.schedule[i].time[0].start + '<br>ticket&nbsp;' + json.schedule[i].ticket + '</dd>');
			}
		}

		// HTML書き出し処理
		$('.scheduleApi').prepend(scheduleArray);
		$('.scheduleApiModal').append(scheduleArrayModal);
	};


// #### 起動 ##################################################

	// APIを呼び出す
	apiCall(setting);

});
