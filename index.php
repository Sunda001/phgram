<?php
require 'phgram.phar';
$bot = new Bot('904830657:AAGNiizYDYmA9qylmJfxG3QVjZJWe3bqrHc');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
}
