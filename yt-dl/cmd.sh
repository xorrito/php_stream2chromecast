#!/bin/bash
youtube-dl :ytwatchlater 'http://www.youtube.com/playlist?list=WL' \
	--newline -f 'best[height<=1080][ext=mp4]' \
	--cookies cookies.txt \
	--mark-watched \
	--download-archive WL-archive.txt \
	-o "../library/%(upload_date)s-%(title)s_-By_%(uploader)s.%(ext)s" \
	--restrict-filenames \
	--write-thumbnail \
	--ignore-config \
	--playlist-reverse \
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