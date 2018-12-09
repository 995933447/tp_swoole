echo "loading"
pid=`pidof swoole_server`
echo $pid
kill -USR1 $pid
echo "loading sucess"