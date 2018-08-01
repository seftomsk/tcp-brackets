#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

try {
    $shortopts = 'p:';
    $longopts = [
        'port:'
    ];
    $options = getopt($shortopts, $longopts);

    $port = $options['p'] ?? $options['port'] ?? 1026;

    if ((int) $port <= 1024) {
        throw new Exception('Enter port > 1024');
    }

    $host = '127.0.0.1';

    set_time_limit(0);

    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket === false) {
        throw new Exception('Could not create socket');
    }
    $socketBind = socket_bind($socket, $host, $port);
    if ($socketBind === false) {
        throw new Exception('Could not bind to socket');
    }
    $socketListen = socket_listen($socket, 4);
    if ($socketListen === false) {
        throw new Exception('Could not set up socket listener');
    }
    $msgsock = socket_accept($socket);
    if ($msgsock === false) {
        throw new Exception('Could not accept incoming connection');
    }

    $output = "Enter any string with brackets please\n";
    $result = socket_write($msgsock, $output, strlen($output));
    if ($result === false) {
        throw new Exception('Could not write output');
    }

    do {
        $result = trim(socket_read($msgsock, 1024));
        if ($result === 'END') {
            socket_close($msgsock);
            break;
        }

        $data = new \Seftomsk\Brackets\Data($result, ['(', ')', ' ']);
        $dataValidate = new \Seftomsk\Brackets\DataValidate($data);
        $dataValidate->validate();
        $research = new \Seftomsk\Brackets\Research($data);
        $research->setOpenBracket('(');
        $research->setCloseBracket(')');
        $res = 'false';
        if ($research->isValid()) {
            $res = 'true';
        }
        $res .= "\n";
        socket_write($msgsock, $res, strlen($res));
    } while(true);
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
} finally {
    socket_close($socket);
}
