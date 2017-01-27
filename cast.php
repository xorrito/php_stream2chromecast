<?php
	ob_end_flush();
	ob_implicit_flush(true);
	
	$directory = "library/";
	
	$descriptorspec = array(
		0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
		2 => array("pipe", "w")    // stderr is a pipe that the child will write to
	);
		
	function list_library(){
		global $directory;
		$mp4s = glob($directory . '*.mp4');
		$parts = glob($directory . '*.part');
		print_r ($parts);
		$i = 0;
		$dp = 0;
		foreach($mp4s as $file){
			$path_parts = pathinfo($file);
			$filename = basename($path_parts['filename']);
			$file_name = str_replace('_', ' ', substr($filename, strpos($filename, '-') + strlen('-')));
			$date_published = substr($filename, 0, strpos($filename, '-'));
			$duration = substr($file_name, strpos($file_name, 'T: ') + strlen('T: '));
			preg_match('/-By (.*?) T:/', $file_name, $by);
			$file_name = substr($file_name, 0, strpos($file_name, '-By'));
			$duration_m = floor($duration / 60);
			if ($duration_m > 60){
				$duration_h = floor($duration_m / 60);
				$duration_m = floor($duration_m % 60);
				if ($duration_m < 10){
					$duration_m = 0 . $duration_m;
				}
				$duration_m = $duration_h . ":" . $duration_m;
			}
			$duration_s = floor($duration % 60);
			if ($duration_s < 10){
				$duration_s = 0 . $duration_s;
			}
			$time = $duration_m.":".$duration_s;
			$month = substr($date_published,4,2);
			$day = substr($date_published,6,2);
			$dateObj   = DateTime::createFromFormat('!m', $month);
			$month = $dateObj->format('F');
			if ($dp !== $date_published){
				print "<h3><hr>".$month." ".$day."</h3>";
				$dp = $date_published;
			}
			$img = $directory . $path_parts['filename'] . '.jpg';
			print "<input id='".$i."' type='radio' name='file' value='";
			print $file;
			print "'><label for='".$i."' class='img_title' onclick='selected(this);'>";
			print "<img src=".$img." height='200'/>";
			if ( $time !== "0:00") {
				print "<span class='time'>".$time."</span>";
			}
			print "<br><span class='title'>".$file_name."</span>";
			if ( isset($by[1]) ){
				print "<br><span class='by'>".$by[1]."</span>";
			}
			print "</label>";
			$i++;
		}
	}
	function isRunning($pid){
    try{
        $result = shell_exec(sprintf("ps %d", $pid));
        if( count(preg_split("/\n/", $result)) > 2){
            return true;
        }
    }catch(Exception $e){}
    return false;
	}
	if ($_SERVER["REQUEST_METHOD"] == "POST"){
		$s2c = "python -u stream2chromecast.py -devicename '10.42.0.45' ";
		if ( isset($_POST["file"]) ) {
			$cmd = $s2c . "../" . $_POST['file'] ;
			print 'Initialising Stream2Chromecast.<br>';
			$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./s2c/'), array());
			fclose($pipes[0]);
			if (is_resource($process)) {
				$p_o = false;
				while ($s = fgets($pipes[1])) {
					if (strpos ($s, 'loading media...') !== false){
						print 'Loading Media Player on device.<br>';
					} else if (strpos($s, 'sending data') !== false){
						print 'Buffering media.<br>';
						$p_o = true;
					} else if (strpos($s, 'done') !== false) {
						print 'Casting Finished!<br>';
					} else if (strpos($s, 'press ctrl-c to stop') !== false){
						print 'File playing!<br>';
					} else if (strpos($s, 'disconnected') !== false){
						print $s . "<br>
							<button onclick='cast();'>
								<i class='material-icons'>replay</i>
							</button>
						"; 
					} else if (strpos($s, "-----------------------------------------") !== false){
						if ($p_o){
							$p_o = false;
						} else {
							$p_o = true;
						}
					} else if ($p_o){
						print $s . "<br>";
					}
				}
				while ($s = fgets($pipes[2])) {
					print "<hr>";
					print $s;
					print "<hr>";
				}
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
			}
		}
		if ( isset($_POST['ytdl_output']) ){
			$pid_file = 'yt-dl/pid';
			if (file_exists($pid_file)){
				$pid = fgets(fopen($pid_file, 'r'));
				if (isRunning($pid)){
					print "YT-DL is running!";
					// ---- will update this code to show progress of yt-dl, including total progress, individual progress, current video and other misc output from yt-dl
					//$cmd = "tail -f stdout.txt --pid=".$pid."";
					//$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./yt-dl/'), array());
					//fclose($pipes[0]);
					//if (is_resource($process)) {
						//while ($s = fgets($pipes[1])) {
							//$s = str_replace("[download]", '', $s);
							
							//print preg_grep('^\[download\].*?\K([0-9.]+\%|\d+ of \d+)', $s);
							
							//preg_match('([0-9.]+\%|\d+ of \d+)', $s, $matches);
							//print "{$matches[0]}";
							
							
							
							//print $s . "<br>";
						//}
						//while ($s = fgets($pipes[2])) {
						//	print "<hr>";
						//	print $s;
						//	print "<hr>";
						//}
					//}
					//fclose($pipes[1]);
					//fclose($pipes[2]);
					//proc_close($process);
				} else {
						print "YT-DL is not running!";
				}
			} else {
				print "No PID file found.";
			}
		}
		if ( isset($_POST["pause"]) ) {
			$cmd = $s2c . "-pause";
			$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./s2c/'), array());
			fclose($pipes[0]);
			echo stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);
		}
		if ( isset($_POST["play"]) ) {
			$cmd = $s2c . "-continue";
			$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./s2c/'), array());
			fclose($pipes[0]);
			echo stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);
		}
		if ( isset($_POST["stop"]) ) {
			$cmd = $s2c . "-stop";
			$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./s2c/'), array());
			fclose($pipes[0]);
			echo stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);
		}
		if ( isset($_POST["archive"]) ){
			if ($directory !== "library/archive/"){
				$path_parts = pathinfo($_POST["file_name"]);
				foreach (glob($directory.$path_parts['filename']."*") as $i){
					rename($i,str_replace($directory, "library/archive/", $i));
				}
			}
		}
		if ( isset($_POST["delete"]) ) {
			$path_parts = pathinfo($_POST["file_name"]);
			foreach (glob($directory.$path_parts['filename']."*") as $i){
				unlink($i);
			}
		}
		if ( isset($_POST["load_list"]) ) {
			list_library();
		}
		if ( isset($_POST["s2c_check"]) ) {
			$fp = fopen("s2c/s2c_status.txt", "w");
			$block = 0;
			if (flock($fp, LOCK_SH|LOCK_NB, $block)) { // do an exclusive lock
   			flock($fp, LOCK_UN);
   			$cmd = "./s2c/stream2chromecast.py -devicename '10.42.0.45' -status" ;
				$output = shell_exec($cmd);
				$fp_unfiltered = fopen("s2c/s2c_status_unfiltered.txt", "w");
   			fwrite($fp_unfiltered, $output);
				//u'playerState': u'PAUSED', "BUFFERING"
				//statusText': u'Ready To Cast'
				function get_string_between($string, $start, $end){
					$string = ' ' . $string;
					$ini = strpos($string, $start);
					if ($ini == 0) return '';
					$ini += strlen($start);
					$len = strpos($string, $end, $ini) - $ini;
					return substr($string, $ini, $len);
				}
				if (strpos($output, "Ready To Cast") !== false){
					$output_fp = "Ready To Cast";
				} else {
					$current = get_string_between($output, "u'currentTime':", ',');
					$duration = get_string_between($output, "duration': ", ",");
					$playerstate = get_string_between($output, "playerState': u'", "',");
					$output_fp = $playerstate.",".$current.",".$duration;
				}
				fwrite($fp, $output_fp);
				print $output_fp;
			}else{
   			print file_get_contents($fp);
			}
			fclose($fp);
		}
	}
?>