<?php
ob_start();
$dl=Catpow\Downloader::get_instance();
$dl->download_next_files();
$dl->render_result();
$res['done']=$dl->is_done();
$res['result']=ob_get_clean();