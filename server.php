<?php
$context = stream_context_create([
    "ssl" => [
        "ciphers" => "DHE-RSA-AES256-SHA:LONG-CIPHER",
        "allow_self_signed" => true,
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
]);

function between(int $number, int $min, int $max) {
    return $number >= $min && $number <= $max;
}

function mask($text)
{
	$b1 = 129;
	$length = strlen($text);
	
	if ($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif ($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif ($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header . $text;
}

function unmask(string $text) {
    $length = ord($text[1]) & 127;
    $offset = 2;
    if ($length === 126) {
        $offset = 4;
    } elseif ($length === 127) {
        $offset = 10;
    }
    $masks = substr($text, $offset, 4);
    $data = substr($text, $offset + 4);
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i % 4];
    }
	return $text;
}

$server = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

$clients = [];

$file = "messages.json";

$exists = file_exists($file);

$messages = $exists ? json_decode(file_get_contents($file), true, 3) : [];

while (true) {
    $changed = [$server];
    $null = null;
    stream_select($changed, $null, $null, 0, 10);
    if (in_array($server, $changed)) {
        $client = stream_socket_accept($server);
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', fread($client, 5000), $matches);
        $key = base64_encode(pack(
            'H*',
            sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));

        fwrite($client, "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Version: 13\r\nSec-WebSocket-Accept: $key\r\n\r\n");
        stream_set_timeout($client, 0);
        foreach ($messages as $message) {
            fwrite($client, mask(json_encode($message, true, 2)));
        }
        $clients[] = $client;
    }
    foreach ($clients as $index => $client) {
        if ($result = fread($client, 5000)) {
            $text = unmask($result);
            if ($text === "" || (strlen($text) === 2 && between(hexdec(bin2hex($text)), 1000, 1015))) {
                unset($clients[$index]);
                continue;
            }
            $decoded = json_decode($text, true, 2);
            if ($decoded // Check if json_decode succeeded
                && $text[0] === "{" // Check if object
                && count($decoded) === 1 // Check if only one key value pair
                && gettype($decoded[array_key_first($decoded)]) === "string") {
                foreach ($clients as $client) {
                    fwrite($client, mask($text));
                }
                $messages[] = $decoded;
                file_put_contents($file, json_encode($messages, 0, 3));
            }
        }
    }
}
?>
