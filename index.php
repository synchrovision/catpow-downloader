<?php
/*
指定のサーバーから全ファイルを順次ダウンロード
１プロセス毎に１フォルダづつ処理
プロセス毎に子フォルダのリストを返してキューに追加
*/
include __DIR__.'/inc/functions.php';
if(!empty($req=json_decode(file_get_contents('php://input'),true))){
	header('Content-type:application/json');
	try{
		session_start();
		if($_SESSION['cpdl_nonce']!==$_SERVER['HTTP_X_CPDL_NONCE']){throw new Exception('Forbidden',403);}
		$action_file=INC_PATH.'/actions/'.basename($req['action']).'.php';
		if(!file_exists($action_file)){throw new Exception('Invalid Action call',403);}
		$res=['status'=>200];
		include $action_file;
	}
	catch(Exception $e){
		$res=[
			'status'=>$e->getCode(),
			'message'=>$e->getMessage()
		];
	}
	echo json_encode($res,0500);
	die();
}
session_start();
$_SESSION['cpdl_nonce']=bin2hex(openssl_random_pseudo_bytes(8));
include INC_PATH.'/template/index.php';