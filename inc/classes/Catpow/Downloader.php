<?php
namespace Catpow;

abstract class Downloader{
	static $interval=1000,$protocol='ftp';
	protected $settings,$files,$pointer=[0],$cache=[],$status,$con;
	const CONFIRM=1,DONE=2,ERROR=4;
	protected function __construct($settings){
		if(!isset($settings['port'])){$settings['port']=(static::$protocol==='sftp')?22:21;}
		assert(isset($settings['host']),'require host');
		assert(isset($settings['user']),'require user');
		assert(isset($settings['password']) || isset($settings['pem']),'require password or pem');
		$this->settings=$settings;
		$this->connect();
		$this->files=$this->scandir('');
	}
	public static function get_instance(){
		if(!isset($_SESSION['cpdl'])){
			include \APP_PATH.'/config.php';
			if(!empty($settings['sftp']) && !file_exists(\INC_PATH.'/vendor/autoload.php')){
				chdir(\INC_PATH);
				exec('composer install');
				chdir(\APP_PATH);
				require_once(INC_PATH.'/vendor/autoload.php');
			}
			$_SESSION['cpdl']=empty($settings['sftp'])?new FTP_Downloader($settings):new SFTP_Downloader($settings);
		}
		return $_SESSION['cpdl'];
	}
	public static function reset_instance(){
		unset($_SESSION['cpdl']);
		return self::get_instance();
	}
	public function get_ftpignore($dir){
		if(isset($this->cache['ftpignore'][$dir])){return $this->cache['ftpignore'][$dir];}
		$rtn=[];
		if(file_exists($f=\ABSPATH.'/'.$dir.'/.ftpignore')){
			foreach(file($f) as $line){
				$line=trim($line);
				if(empty($line)){continue;}
				if(substr($line,0,1)==='!'){
					$rtn[$dir]['keep'][]=substr($line,1);
				}
				else{
					$rtn[$dir]['ignore'][]=$line;
				}
			}
		}
		if(!empty($dir)){
			$rtn=array_merge(
				$rtn,
				$this->get_ftpignore(
					(strpos($dir,'/')!==false)?dirname($dir):''
				)
			);
		}
		return $this->cache['ftpignore'][$dir]=$rtn;
	}
	public function is_file_to_download($file){
		if(!in_array($file['type'],['dir','file'])){return false;}
		if($file['name']==='.git'){return false;}
		foreach($this->get_ftpignore(dirname($file['path'])) as $dir=>$ftpignore){
			$f=$file['path'];
			if(isset($ftpignore['keep'])){
				foreach($ftpignore['keep'] as $pattern){
					if(fnmatch($pattern,$f)){continue 2;}
				}
			}
			foreach($ftpignore['ignore'] as $pattern){
				if(fnmatch($pattern,$f)){return false;}
			}
		}
		return true;
	}
	
	public function download_next_files(){
		$files=&$this->files;
		for($d=0,$l=count($this->pointer);$d<$l;$d++){
			$p=$this->pointer[$d];
			if(empty($files[$p]) || $files[$p]['type']!=='dir'){break;}
			if(empty($files[$p]['children'])){
				$files[$p]['children']=$this->scandir($files[$p]['path']);
			}
			if(!is_dir($files[$p]['path'])){mkdir($files[$p]['path'],$files[$p]['mode']);}
			$files=&$files[$p]['children'];
		}
		if(empty($this->pointer[$d])){$this->pointer[$d]=0;}
		$start=microtime(true);
		for($l=count($files);$this->pointer[$d]<$l;$this->pointer[$d]++){
			if(microtime(true)-$start>1){return;}
			$p=$this->pointer[$d];
			if(empty($files[$p])){break;}
			if($files[$p]['type']==='dir'){
				$this->pointer[]=0;
				if(empty($files[$p]['children'])){
					$files[$p]['children']=$this->scandir($files[$p]['path']);
				}
				return;
			}
			$this->download($files[$p]['path']);
			$files[$p]['status']='done';
		}
		if($d<1){$this->status='finished';return;}
		array_pop($this->pointer);
		$this->pointer[$d-1]++;
		$files=null;
	}
	
	abstract public function connect();
	abstract public function scandir($dir);
	abstract public function download($file);
	abstract public function close();
	
	public function is_done(){
		return $this->status==='finished';
	}
	public function render_result(){
		$this->render_result_of_files($this->files);
	}
	public function render_result_of_files($files){
		static $colors=[
			'done'=>'text-success',
			'disabled'=>'text-muted',
			'failed'=>'text-warning',
			'error'=>'text-danger'
		];
		foreach($files as $file){
			printf(
				'<div class="%s fs-6 d-flex"><span class="material-icons %s fs-5 me-2">%s</span><span>%s</span></div>',
				isset($colors[$file['status']])?$colors[$file['status']]:'text-light',
				$file['type']==='dir'?'text-info':'text-light',
				$file['type']==='dir'?'folder':'article',
				$file['name']
			);
			if($file['type']==='dir' && !empty($file['children'])){
				echo '<div class="ms-4">';
				$this->render_result_of_files($file['children']);
				echo '</div>';
			}
		}
	}
	public function log_error($message){
		$h=fopen(APP_PATH.'/download_error.log','a');
		fwrite($h,'['.date('Y-m-d').']'.$message);
		fclose($h);
	}

	function __sleep(){
		return ['settings','files','pointer','cache','status'];
	}
	function __wakeup(){
		$this->connect();
	}
}


?>