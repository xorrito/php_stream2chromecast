<?php
	ob_end_flush();
	ob_implicit_flush(true);
	// ---- since the new yt-dl script download in only mp4, this array will be removed.
	$file_types = array(".mp4", ".webm");
	function list_library(){
		// ---- spacer for testing
		print "<br><br><br>";
		
		// ---- This random key will be used to lock the s2c_checker to one single connection and propogate the results to secondary sessions.
		// ---- this is to avoid running the s2c --status command excesively
		print mt_rand();
		$allfiles = glob('library/*');
		
		// ---- test code to try to imprube the list script
		//foreach($allfiles as $file_mimo){
		//	if ( "video/mp4" !==  mime_content_type($file_mimo) and "image/jpeg" !== mime_content_type($file_mimo))
		//	print $file_mimo . " ---- " . mime_content_type($file_mimo) . "<br>";
		//}
		//print "<hr>";
		
		
		
		
		
		$files = glob('library/*.mp4');
		$i = 0;
		$dp = 0;
		foreach($files as $file){
			global $file_types;
			if ( "archive" !== $file ){
				// ---- this was a fix for files containing the + sign, will change this to use the htmlspecialchars command.
				// ---- will also add a way to find out the length of each video and display it.
				if ( strpos($file,'+') > 0){
					print "File has plus sign!";
					$new_file_name = str_replace("+", "plus", $file);
					rename($file,$new_file_name);
					$file = $new_file_name;
					$file_name = str_replace($file_types, '', str_replace('_', ' ', substr(basename($new_file_name), strpos(basename($new_file_name), '-') + strlen('-'))));
					$file_name = str_replace(" -By ", "<br>", $file_name);
					$img_file_name = str_replace($file_types, '.jpg', $file);
					$new_img_file_name = str_replace($file_types, '.jpg', $new_file_name);
					rename($img_file_name,$new_img_file_name);
				} else {
					$file_name = str_replace($file_types, '', str_replace('_', ' ', substr(basename($file), strpos(basename($file), '-') + strlen('-'))));
					$file_name = str_replace(" -By ", "<br>", $file_name)."";
				}
				$date_published = substr(basename($file), 0, strpos(basename($file), '-'));
				$month = substr($date_published,4,2);
				$day = substr($date_published,6,2);
				$dateObj   = DateTime::createFromFormat('!m', $month);
				$month = $dateObj->format('F');
				if ($dp !== $date_published){
					print "<h3><hr>".$month." ".$day."</h3>";
					$dp = $date_published;
				}
				$img = str_replace($file_types, '.jpg', $file);
				print "<input id='".$i."' type='radio' name='file' value='";
				print $file;
				print "'><div class='img_title'><label for='".$i."'>";
				print "<img src=".$img." height='200'><br>";
				print  $file_name;
				print "</label></div>";
				$i++;
			}
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
		$descriptorspec = array(
			0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
			2 => array("pipe", "w")    // stderr is a pipe that the child will write to
		);
		if ( isset($_POST["file"]) ) {
			$cmd = $s2c . "../" . $_POST['file'] ;
			$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./s2c/'), array());
			fclose($pipes[0]);
			if (is_resource($process)) {
				$p_o = true;
				while ($s = fgets($pipes[1])) {
					if (strpos($s, 'done') !== false) {
						print 'Casting Finished!<br>What would you like to do?<br><div class="buttons">
								<button onclick="delete_file();">
									<i class="material-icons">delete</i>
								</button>
								<button onclick="archive();">
									<i class="material-icons">archive</i>
								</button>
								<button onclick="close_player();">
									<i class="material-icons">forward</i>
								</button>
							</div>';
					} else if (strpos($s, 'press ctrl-c to stop') !== false){
						print 'File playing!<br>
							<div class="buttons">
								<button onclick="pause();" id="play_pause_button">
									<i class="material-icons" id="play_pause">pause</i>
								</button>
								<button onclick="stop();">
									<i class="material-icons">stop</i>
								</button>
								<input id="file_name" type="hidden" value="' . $_POST['file'] . '"/>
							</div>';
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
			$path_parts = pathinfo($_POST["file_name"]);
			foreach (glob("library/".$path_parts['filename']."*") as $i){
				rename($i,str_replace("library/", "library/archive/", $i));
			}
		}
		if ( isset($_POST["delete"]) ) {
			$path_parts = pathinfo($_POST["file_name"]);
			foreach (glob("library/".$path_parts['filename']."*") as $i){
				unlink($i);
			}
		}
		if ( isset($_POST["load_list"]) ) {
			list_library();
		}
		if ( isset($_POST["s2c_check"]) ) {
			
			
			
			// ---- needs a first connected session lock to avoid running the s2c -status command too often and flooding the command, this has proven to delay sending the -pause, -continue, and -stop commands.
			
				$cmd = "./s2c/stream2chromecast.py -devicename '10.42.0.45' -status" ;
				$output = shell_exec($cmd);
				//u'playerState': u'PAUSED', "BUFFERING", 
				if (strpos($output, "Now Casting") !== false){
					function get_string_between($string, $start, $end){
						$string = ' ' . $string;
						$ini = strpos($string, $start);
						if ($ini == 0) return '';
						$ini += strlen($start);
						$len = strpos($string, $end, $ini) - $ini;
						return substr($string, $ini, $len);
					}
					$current = get_string_between($output, "u'currentTime':", ',');
					$duration = get_string_between($output, "duration': ", ",");
					$playerstate = get_string_between($output, "playerState': u'", "',");
					print $playerstate.",".$current.",".$duration;
					//u'muted': False, u'level': 1
				}
				
				
				
				
				
				
		}
	}
?>