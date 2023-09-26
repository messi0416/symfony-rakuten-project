var code
function setCode(p){
  code = "#" + p;
}

/********************************************************************************

	SYNCER 〜 知識、感動をみんなと同期(Sync)するブログ

	* 配布場所
	https://syncer.jp/jquery-modal-window

	* 最終更新日時
	2015/08/17 15:55

	* 作者
	あらゆ

	** 連絡先
	Twitter: https://twitter.com/arayutw
	Facebook: https://www.facebook.com/arayutw
	Google+: https://plus.google.com/114918692417332410369/
	E-mail: info@syncer.jp

	※ バグ、不具合の報告、提案、ご要望など、お待ちしております。
	※ 申し訳ありませんが、ご利用者様、個々の環境における問題はサポートしていません。

********************************************************************************/


$(function(){

//モーダルウィンドウを出現させるクリックイベント
$(".modal-open").click( function(){

　// 20170531独自：モーダルのプレビューエリアにHTMLを渡す
  if($(code).val() == null){
	  $('#preview').html($('#appbundle_malldesign_designHtml').val());
	}else{
		$('#preview').html($(code).val());
	}

	//キーボード操作などにより、オーバーレイが多重起動するのを防止する
	$( this ).blur() ;	//ボタンからフォーカスを外す
	if( $( "#modal-overlay" )[0] ) return false ;		//新しくモーダルウィンドウを起動しない (防止策1)
	//if($("#modal-overlay")[0]) $("#modal-overlay").remove() ;		//現在のモーダルウィンドウを削除して新しく起動する (防止策2)

	//オーバーレイを出現させる
	$( "body" ).append( '<div id="modal-overlay"></div>' ) ;
	$( "#modal-overlay" ).fadeIn( "slow" ) ;

	//コンテンツをセンタリングする
	centeringModalSyncer() ;

	//コンテンツをフェードインする
	$( "#modal-content" ).fadeIn( "slow" ) ;

	//[#modal-overlay]、または[#modal-close]をクリックしたら…
	$( "#modal-overlay,#modal-close" ).unbind().click( function(){

		//[#modal-content]と[#modal-overlay]をフェードアウトした後に…
		$( "#modal-content,#modal-overlay" ).fadeOut( "slow" , function(){

			//[#modal-overlay]を削除する
			$('#modal-overlay').remove() ;

		} ) ;

	} ) ;

} ) ;

//リサイズされたら、センタリングをする関数[centeringModalSyncer()]を実行する
$( window ).resize( centeringModalSyncer ) ;

	//センタリングを実行する関数
	function centeringModalSyncer() {

		//画面(ウィンドウ)の幅、高さを取得
		var w = $( window ).width() ;
		var h = $( window ).height() ;

		// コンテンツ(#modal-content)の幅、高さを取得
		// jQueryのバージョンによっては、引数[{margin:true}]を指定した時、不具合を起こします。
//		var cw = $( "#modal-content" ).outerWidth( {margin:true} );
//		var ch = $( "#modal-content" ).outerHeight( {margin:true} );
		var cw = $( "#modal-content" ).outerWidth();
		var ch = $( "#modal-content" ).outerHeight();

		//センタリングを実行する
		$( "#modal-content" ).css( {"left": ((w - cw)/2) + "px","top": ((h - ch)/2) + "px"} ) ;

	}

} ) ;
