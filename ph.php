<?php
require 'phgram.phar';
$bot = new Bot('904830657:AAF56gfJU6lNxbTTxl-M3CeI7UjmR3oPWqs');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
}
?>
