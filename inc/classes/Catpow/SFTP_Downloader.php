<?php
namespace Catpow;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

class SFTP_Downloader extends Downloader{
	static
		$protocol='sftp',
		$filetypes=['','file','dir','symlink','special','unknown','scket','char_device','fifo'];
	public function connect(){
		extract($this->settings);
		$sftp=new SFTP($host,$port);
		if(isset($pem)){
			ob_start();
			passthru("cat {$pem}");
			$key=PublicKeyLoader::load(ob_get_clean());
			if(!$sftp->login($user,$key)){
				throw new \Exception("sftp failed to login with identical file {$pem}",403);
			}
		}
		else if(isset($password)){
			if(!$sftp->login($user,$password)){
				throw new \Exception("sftp failed to login with password",403);
			}
		}
		if(isset($root_path)){
			if(!$sftp->chdir($root_path)){
				throw new \Exception("sftp failed to change directory to {$root_path}",403);
			}
		}
		$this->con=$sftp;
	}
	public function scandir($dir){
		$rtn=[];
		$fs=$this->con->rawlist();
		foreach($fs as $f){
			$f=[
				'type'=>self::$filetypes[$f['type']],
				'name'=>$f['filename'],
				'path'=>trim($dir.'/'.$f['filename'],'/'),
				'size'=>isset($f['size'])?$f['size']:null,
				'mode'=>$f['mode']&0777,
				'status'=>0
			];
			if($this->is_file_to_download($f)){
				$rtn[]=$f;
			}
		}
		return $rtn;
	}
	public function download($file){
		$this->con->get($file,$file);
	}
	public function close(){
		
	}
}


?>