#!/bin/bash
check_connectivity()(
	if ping -q -c 1 -W 1 8.8.8.8 >/dev/null; then
	  echo "IPv4 is up"
	  if ping -q -c 1 -W 1 google.com >/dev/null; then
		  echo "The network is up"
		  case "$(curl -s --max-time 2 -I http://google.com | sed 's/^[^ ]*  *\([0-9]\).*/\1/; 1q')" in
			  [23]) echo "HTTP connectivity is up";;
			  5) echo "The web proxy won't let us through"
				sleep 100
				check_connectivity
				;;
			  *) echo "The network is down or very slow"
				sleep 100
				check_connectivity
				;;
			esac
		else
		  echo "The network is down"
		  sleep 100
		  check_connectivity
		fi
	else
	  echo "IPv4 is down"
	  sleep 100
	  check_connectivity
	fi
)
youtube-dl :ytwatchlater 'http://www.youtube.com/playlist?list=WL' \
	--newline -f 'best[height<=1080][ext=mp4]' \
	--cookies cookies.txt \
	--mark-watched \
	--download-archive WL-archive.txt \
	-o "../library/%(upload_date)s-%(title)s_-By_%(uploader)s_T:_%(duration)s.%(ext)s" \
	--restrict-filenames \
	--write-thumbnail \
	--ignore-config \
	--playlist-reverse \
	--ignore-errors \
	2>&1 > stdout.txt &
pid=$!
echo $pid > pid
tail -f --pid=$pid stdout.txt | 
grep --line-buffered -oP '^\[download\].*?\K([0-9.]+\%|\d+ of \d+)' |
sed --unbuffered '/%$/s/\([0-9]*\).*/\1/; /\([0-9]\ of\ [0-9]\)/s/.*/XXX\'$'\nDownloading video &\\nXXX/' |
dialog --title "Youtube-dl PID:$pid" --gauge "Starting Download" 10 40 0 
echo "YT-DL Terminated, cleaning up..."
rm pid
mv stdout.txt stdout_old.txt
echo "Done!"
sleep 100
check_connectivity