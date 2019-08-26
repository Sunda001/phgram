<?php require 'phgram.phar';
$bot = new Bot('904830657:AAHcw78REwpe1ga8U5YKVKWP-dlIe5W4xCE');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
}
?>
