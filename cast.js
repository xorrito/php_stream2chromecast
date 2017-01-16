var c_p = "";
function cast(){
	if (! document.querySelector('input[name = "file"]:checked') ) {
		alert("Nothing Selected");
	} else {
		f_s = "file=" + document.querySelector('input[name = "file"]:checked').value;
		http = new XMLHttpRequest();
		http.open('POST', "cast.php", true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.send(f_s);
		document.getElementById("output_holder").className = "undocked";
		http.onreadystatechange = function() {
			if (this.readyState >= 3 && this.status == 200) {
				c_o = document.getElementById("cast_output");
				c_o.innerHTML = http.responseText;
			}
		};
	}
}
function show_hide(){
	holder = document.getElementById("output_holder");
	chevron = document.getElementById("show_hide");
	if ( holder.className == "docked"){
		chevron.innerHTML = "chevron_right";
		holder.className = "undocked";
	} else {
		chevron.innerHTML = "chevron_left";
		holder.className = "docked";
	}
}
function pause(){
	http2 = new XMLHttpRequest();
	http2.open('POST', "cast.php", true);
	http2.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http2.send("pause");
	http2.onload = function() {
		document.getElementById("play_pause").innerHTML='play_arrow';
		document.getElementById("play_pause_button").onclick = play;
	};
}
function play(){
	http2 = new XMLHttpRequest();
	http2.open('POST', "cast.php", true);
	http2.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http2.send("play");
	http2.onload = function() {
		document.getElementById("play_pause").innerHTML='pause';
		document.getElementById("play_pause_button").onclick = pause;
	};
}
function stop(){
	http2 = new XMLHttpRequest();
	http2.open('POST', "cast.php", true);
	http2.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http2.send("stop");
	http2.onload = function() {
		close_player();
	};
}
function close_player(){
	document.getElementById("output_holder").className='hidden';
	document.getElementById("cast_output").innerHTML="Loading! Please Wait.";	
	document.getElementById("show_hide").innerHTML = "chevron_right";
}
function archive(){
	// ---- add a confirm prompt before sending command.
	http2 = new XMLHttpRequest();
	http2.open('POST', "cast.php", true);
	http2.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	if ( document.getElementById("file_name") ) {
		f_n = document.getElementById("file_name").value;
	} else {
		f_n = document.querySelector('input[name = "file"]:checked').value;
	}
	http2.send("archive&file_name=" + f_n);
	http2.onload = function() {
		close_player();
		load_list();
	};
}
function delete_file(){
	// ---- add a confirm prompt before sending command.
	http2 = new XMLHttpRequest();
	http2.open('POST', "cast.php", true);
	http2.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	if ( document.getElementById("file_name") ) {
		f_n = document.getElementById("file_name").value;
	} else {
		f_n = document.querySelector('input[name = "file"]:checked').value;
	}
	http2.send("delete&file_name=" + f_n);
	http2.onload = function() {
		close_player();
		load_list();
	};
}
function load_list(){
	http0 = new XMLHttpRequest();
	http0.open('POST', "cast.php", true);
	http0.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http0.send("load_list=true");
	http0.onload = function() {
		if (this.readyState >= 3 && this.status == 200) {
			file_list = document.getElementById("file_list");
			file_list.innerHTML = http0.responseText;
		}
	};
}
function init(){
	load_list();
	// ---- add loop for yt-dl
	check_ytdl();
	s2c_check();
}
function expand(){
	var yto=document.getElementById("ytdl_tracker");
	if (yto){
		yto.style.height=(yto.style.height==='calc(100% - 29px)')?'29px':'calc(100% - 29px)';
	}
}
function check_ytdl(){
	http1 = new XMLHttpRequest();
	http1.open('POST', "cast.php", true);
	http1.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	http1.send('ytdl_output');
	http1.onreadystatechange = function() {
		if (this.readyState >= 3 && this.status == 200) {
			ytdlt = document.getElementById("ytdl_tracker");
			ytout = http1.responseText.split(/\r?\n/);
			//ytout.slice(-1)[0];
			ytdlt.innerHTML = ytout;
			document.getElementById("v_bar").style.width = http1.responseText;
		}
	};
}
function s2c_check(){
	http3 = new XMLHttpRequest();
	http3.open('POST', "cast.php", true);
	http3.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	http3.send('s2c_check');
	status_buttons = document.getElementById("player_status");
	http3.onload = function() {
		if (this.readyState >= 3 && this.status == 200) {
			player_current = document.getElementById("player_current");
			player_duration = document.getElementById("player_duration");
			seek_div = document.getElementById("seek");
			output = http3.responseText.split(",");
			status_buttons.innerHTML = output[0];
			if ( output[0] == "PLAYING"){
				// ---- may run a local counter to provide a smother seek bar
				seek_state =  (output[1] / output[2] * 100).toFixed(2);
				current_m = Math.floor(output[1] / 60 );
				current_s = Math.floor(output[1] % 60 );
				duration_m = Math.floor(output[2] / 60 );
				duration_s = Math.floor(output[2] % 60 );
				player_current.innerHTML = current_m + "m " + current_s + "s";
				player_duration.innerHTML = duration_m + "m " + duration_s + "s";
				seek_div.style.width = seek_state + "%";
				status_buttons.innerHTML  = '<button onclick="stop();"><i class="material-icons">stop</i></button><button onclick="pause();" id="play_pause_button"><i class="material-icons" id="play_pause">pause</i></button>';
				setTimeout(function(){
					s2c_check();
				}, 500);
			} else if ( output[0] == "PAUSED") {
				status_buttons.innerHTML  = '<button onclick="stop();"><i class="material-icons">stop</i></button><button onclick="play();" id="play_pause_button"><i class="material-icons" id="play_pause">play</i></button>';
			} else {
				setTimeout(function(){
					s2c_check();
				}, 2000);
			}
		}
	};
}