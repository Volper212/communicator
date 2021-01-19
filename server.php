<?php
$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, "127.0.0.1", 8000);

$clients = [];

class Listener extends Thread {
    public function run() {
        global $server;
        global $clients;
        while (true) {
            socket_listen($server);
            $client = socket_accept($server);

            $request = socket_read($client, 5000);
            preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
            $key = base64_encode(pack(
                'H*',
                sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
            ));

            $headers = "HTTP/1.1 101 Switching Protocols\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Version: 13\r\n"
                . "Sec-WebSocket-Accept: $key\r\n\r\n";
            socket_write($client, $headers, strlen($headers));
            $clients[] = $client;
        }
    }
}

$listener = new Listener;
$listener->start();

while (true) {
    sleep(1);
    $message = "bruh";
    foreach ($clients as $client) {
        socket_write($client, chr(129) . chr(strlen($message)) . $message);
    }
}
?>
