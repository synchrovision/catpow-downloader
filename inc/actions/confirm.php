<?php
ob_start();
$dl=Catpow\Downloader::get_instance();
$dl->render_result();
$res['result']=ob_get_clean();