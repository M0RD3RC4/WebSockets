<?php

try {
    $sql = new PDO('mysql:host=localhost;dbname=websocket;encoding=utf8;port=3306', 'root', '');
}
catch(PDOException $e) {
    die('Unable to connect to the database');
}

$date = date('Y-m-d G:i:s', time());
$command = $sql->prepare('SELECT id FROM servers WHERE timeout < ?');
$command->execute(array($date));
if($command->rowCount() > 0) {
    foreach($command as $value) {
        $sql->query('DELETE FROM servers WHERE id = '.$value['id']);
    }
}

if(isset($_GET['port'])) {
    if(is_numeric($_GET['port'])) $port = $_GET['port'];
    else die('Invalid port');
    $command = $sql->query('SELECT port, timeout FROM servers WHERE port = '.$port);
    if($command->rowCount() != 0) die('Port '.$port.' is in use');
    $command = $sql->prepare('INSERT INTO servers (port, timeout) VALUES (?, ?)');
    $date = date('Y-m-d G:i:s', strtotime('+1 hour'));
    $command->execute(array($port, $date));

    set_time_limit(3600);
    $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($server, '0.0.0.0', $port);
    socket_listen($server);
    $client = socket_accept($server);
    socket_write($client, 'x0y500|0|0|0|0|0|');
    while(true) {
        if(!$client) break;
        $request = socket_read($client, 30);
        $x = substr($request, 1, strpos($request, 'y') - 1);
        $y = substr($request, strpos($request, 'y') + 1 );
        socket_write($client, 'x'.$x.'y'.$y);
    }
    //socket_write($client, $request);
}
?>