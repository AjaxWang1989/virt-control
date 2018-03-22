<?php
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
header('Content-Type:text/json;charset=utf-8');
include './Libvirt.php';
include './Domain.php';

//define('TOKEN_PATH', dirname(__FILE__) . '/../kvm_data/tokens' . DIRECTORY_SEPARATOR);
//define('FTP_PATH', dirname(__FILE__) . '/../kvm_data/ftp' . DIRECTORY_SEPARATOR);
//define('IMAGE_PATH', dirname(__FILE__) . '/../kvm_data/image' . DIRECTORY_SEPARATOR);
//define('BACKING_IMAGE_PATH', dirname(__FILE__) . '/../kvm_data/image/training' . DIRECTORY_SEPARATOR);
define('TOKEN_PATH', '/home/www/kvm_data/tokens' . DIRECTORY_SEPARATOR);
define('FTP_PATH', '/home/www/kvm_data/ftp' . DIRECTORY_SEPARATOR);
define('IMAGE_PATH', '/home/www/kvm_data/image' . DIRECTORY_SEPARATOR);
define('BACKING_IMAGE_PATH', '/home/www/kvm_data/image/training' . DIRECTORY_SEPARATOR);

$ALLOWED_IP=array('118.186.221.125','127.0.0.1');
//check_ip($ALLOWED_IP);

$tid = $did = $bid = $cat = $act = $img = NULL;
if(isset($_GET['tid'])){$tid = $_GET['tid'];}
if(isset($_GET['did'])){$did = $_GET['did'];}
if(isset($_GET['bid'])){$bid = $_GET['bid'];}
if(isset($_GET['cat'])){$cat = $_GET['cat'];}
if(isset($_GET['act'])){$act = $_GET['act'];}
if(isset($_GET['img'])){$img = $_GET['img'];}

$imgname = getImgName($cat,$tid);
$actresult = [];

if($act == 'list'){
	$files = Libvirt::getFtpImages();
	$images = [];
	foreach ($files as $file) {
		$images[] = [
		'id' => $file,
		'parent' => '#',
		'text' => $file,
		'icon' => false
		];
	}
	echo json_encode($images);
}
/*
if($act == 'add'){
	if(!is_null($img) && $imgname){
		if(file_exists(IMAGE_PATH . $imgname)){
			//echo -3;
			$actresult = ['res' => -3];
			echo json_encode($actresult);
		}else{
			if(rename(FTP_PATH . $img , IMAGE_PATH . $imgname)){
				//echo 0;
				$actresult = ['res' => 0];
				echo json_encode($actresult);
			}else{
				//echo -1;
				$actresult = ['res' => -1];
				echo json_encode($actresult);
			}
		}
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
*/
if($act == 'create'){
	if(!is_null($img) && !is_null($did) && $imgname){
		if(getDomainStatus($did) === false){
			if(file_exists(IMAGE_PATH . $imgname)){
				@unlink(IMAGE_PATH . $imgname);
			}
			if(rename(FTP_PATH . $img , IMAGE_PATH . $imgname)){
				Domain::created('domain-' . $did , $imgname);
				//echo domainStart($did);
				$res = domainStart($did);
				$actresult = ['res' => $res];
				echo json_encode($actresult);
			}else{
				//echo -1;
				$actresult = ['res' => -1];
				echo json_encode($actresult);
			}
		}else{
			//echo -3;
			$actresult = ['res' => -3];
			echo json_encode($actresult);
		}
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
if($act == 'delete'){
	if(!is_null($bid)){
		if(!is_null($did)){
			//if(file_exists(BACKING_IMAGE_PATH . 'training-' . $bid . '.qcow2')){
			if(getDomainStatus($did) === false){
				//echo -3;
				$actresult = ['res' => -3];
				echo json_encode($actresult);
			}else{
				//echo Domain::deleted('domain-' . $did , 'training-' . $bid . '.qcow2' , 1);
				$res = Domain::deleted('domain-' . $did , 'training-' . $bid . '.qcow2' , 1);
				$actresult = ['res' => $res];
				echo json_encode($actresult);
			}
		}else{
			//echo -9;
			$actresult = ['res' => -9];
			echo json_encode($actresult);
		}

	}else{
		if(!is_null($did) && $imgname){
			//if(file_exists(IMAGE_PATH . $imgname)){
			if(getDomainStatus($did) === false){
				//echo -3;
				$actresult = ['res' => -3];
				echo json_encode($actresult);
			}else{
				//echo Domain::deleted('domain-' . $did , $imgname);
				$res = Domain::deleted('domain-' . $did , $imgname);
				$actresult = ['res' => $res];
				echo json_encode($actresult);
			}
		}else{
			//echo -9;
			$actresult = ['res' => -9];
			echo json_encode($actresult);
		}
	}
}
if($act == 'backing'){
	if(!is_null($did) && !is_null($bid) && $imgname){
		if(file_exists(BACKING_IMAGE_PATH . 'training-' . $bid . '.qcow2')){
			//echo -3;
			$actresult = ['res' => -3];
			echo json_encode($actresult);
		}else{
			if(execmd("qemu-img create -b " . IMAGE_PATH . $imgname . " -f qcow2 " . BACKING_IMAGE_PATH . "training-" . $bid . ".qcow2") == 0) {
				Domain::created('domain-' . $did , "training-" . $bid . ".qcow2" , 1);
				//echo domainStart($did);
				$res = domainStart($did);
				$actresult = ['res' => $res];
				echo json_encode($actresult);
			}else{
				//echo -1;
				$actresult = ['res' => -1];
				echo json_encode($actresult);
			}
		}
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
if($act == 'start'){
	if(!is_null($did)){
		//echo domainStart($did);
		$res = domainStart($did);
		$actresult = ['res' => $res];
		echo json_encode($actresult);
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
if($act == 'stop'){
	if(!is_null($did)){
		//echo domainStop($did);
		$res = domainStop($did);
		$actresult = ['res' => $res];
		echo json_encode($actresult);
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
if($act == 'status'){
	if(!is_null($did)){
		//echo getDomainStatus($did);
		$res = getDomainStatus($did);
		$actresult = ['res' => $res];
		echo json_encode($actresult);
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
if($act == 'view'){
	if(!is_null($did)){
		$tokenfile = TOKEN_PATH . "domain-" . $did . ".ini";
		if(file_exists($tokenfile)){
			$tfhandle = fopen($tokenfile, "r");
			$tokeninfo=fread($tfhandle,filesize($tokenfile));
			fclose($tfhandle);
			$token = substr($tokeninfo,0,6);
			$actresult = ['res' => $token];
			echo json_encode($actresult);
		}else{
			//echo -3;
			$actresult = ['res' => -3];
			echo json_encode($actresult);
		}
	}else{
		//echo -9;
		$actresult = ['res' => -9];
		echo json_encode($actresult);
	}
}
function getImgName($cat,$tid){
	if(is_null($cat) && is_null($tid)){
		return false;
	}else{
		$tmp = '';
		if($cat == 1) $tmp = 'training-single-';
		if($cat == 2) $tmp = 'training-comprehensive-';
		$tmp .= $tid . '.qcow2';
		return $tmp;
	}
}
function launchBgProcess($call){
    pclose(popen($call.'  2>&1 >/dev/null &', 'r'));
    return true;
}
function execmd($cmd, &$out = null)
{
	$desc = array(
		1 => array("pipe", "w"),
		2 => array("pipe", "w")
	);

	$pipes = NULL;
	$proc = proc_open($cmd, $desc, $pipes);

	$ret = stream_get_contents($pipes[1]);
	$err = stream_get_contents($pipes[2]);

	fclose($pipes[1]);
	fclose($pipes[2]);

	$retVal = proc_close($proc);

	if (func_num_args() == 2) $out = array($ret, $err);
	return $retVal;
}
function domainStart($did){
	$domainstatus = getDomainStatus($did);
	if ($domainstatus === false) {
		return -1;
	}else{
		if ($domainstatus == 1) {
			return -3;
		}else{
			return Domain::start('domain-' . $did);
		}
	}
}
function domainStop($did){
	$domainstatus = getDomainStatus($did);
	if ($domainstatus === false) {
		return -1;
	}else{
		if ($domainstatus == 1) {
			return Domain::stop('domain-' . $did);
		}else{
			return -3;
		}
	}
}
function getDomainStatus($did){
	return Domain::isRunning('domain-' . $did);
}
function check_ip($ALLOWED_IP){
	$IP=getIP();
	$check_ip_arr= explode('.',$IP);
	if(!in_array($IP,$ALLOWED_IP)) {
		foreach ($ALLOWED_IP as $val){
		  if(strpos($val,'*')!==false){
		  	 $arr=array();
		  	 $arr=explode('.', $val);
		  	 $bl=true;
		  	 for($i=0;$i<4;$i++){
		  	 	if($arr[$i]!='*'){
		  	 		if($arr[$i]!=$check_ip_arr[$i]){
		  	 			$bl=false;
		  	 			break;
		  	 		}
		  	 	}
		  	 }
		  	 if($bl){
		  	 	return;
		  	 	die;
		  	 }
		  }
		}
		header('HTTP/1.1 403 Forbidden');
		echo "Access forbidden";
		die;
	}
}
function getIP(){
  return isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : (isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] : $_SERVER["REMOTE_ADDR"]);
}
?>