<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>ControlPanel</title>
<script type="module" src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> 
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script>
	function app(){
		const con=axios.create({
			baseURL:location.href,
			headers:{
				'X-CPDL-NONCE':'<?=$_SESSION['cpdl_nonce']?>'
			}
		});
		const downloadNextFiles=(app)=>{
			con.post('/',{
				action:'download'
			}).then((res)=>{
				app.result=res.data.result;
				if(res.data.done){app.phase='finished';return;}
				if(app.phase==='download'){downloadNextFiles(app);}
			});
		};
		const showResult=(app,res)=>{
			if(res.data.status===200){
				app.result=res.data.result;
			}
			else{
				app.result='<div class="text-danger">'+res.data.message+'</div>';
			}
		};
		return {
			baseURL:location.href,
			phase:'confirm',
			result:'',
			startDownload(){
				this.phase='download';
				downloadNextFiles(this);
			},
			resetDownload(){
				con.post('/',{action:'reset'}).then((res)=>{this.phase='confirm';showResult(this,res)});
			},
			stopDownload(){
				this.phase='stop';
			},
			init(){
				con.post('/',{action:'confirm'}).then((res)=>{showResult(this,res)});
			}
		};
	}
</script>
</head>
<body>
<div class="container-fluid p-0" x-data="app()">
	<div class="bg-white shadow p-3 position-sticky top-0">
		<div class="text-center">
			<button type="button" class="btn btn-secondary" x-show="phase!='download'" @click="resetDownload()">Reset</button>
			<button type="button" class="btn btn-primary" x-show="phase=='confirm'" @click="startDownload()">Download</button>
			<button type="button" class="btn btn-secondary" x-show="phase=='download'" @click="stopDownload()">Stop</button>
			<button type="button" class="btn btn-primary" x-show="phase=='stop'" @click="startDownload()">Restart</button>
		</div>
	</div>
	<div class="text-white bg-dark p-3 fs-6 min-vh-100" x-html="result"></div>
</div>
</body>
</html>