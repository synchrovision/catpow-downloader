<?php
namespace Catpow;

class FTP_Downloader extends Downloader{
	public function connect(){
		extract($this->settings);
		$con=ftp_connect($host,$port);
		if(!ftp_login($con,$user,$password)){
			throw new \Exception("FTP login failed",403);
		}
		ftp_pasv($con,true);
		if(isset($root_path)){
			if(!ftp_chdir($con,$root_path)){
				throw new \Exception("ftp failed to change directory to {$root_path}",403);
			}
		}
		return $this->con=$con;
	}
	public function scandir($dir){
		$rtn=[];
		$fs=ftp_mlsd($this->con,$dir);
		if(empty($fs)){return [];}
		foreach($fs as $f){
			$f=[
				'type'=>$f['type'],
				'name'=>$f['name'],
				'path'=>trim($dir.'/'.$f['name'],'/'),
				'size'=>isset($f['size'])?$f['size']:null,
				'mode'=>octdec($f['UNIX.mode']),
				'status'=>0
			];
			if($this->is_file_to_download($f)){
				$rtn[]=$f;
			}
		}
		return $rtn;
	}
	public function download($file){
		ftp_get($this->con,$file,$file);
	}
	public function close(){
		ftp_close($this->con);
	}
}


?>