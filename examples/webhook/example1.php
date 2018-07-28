<?php
# with inline keyboard
include 'bot.class.php';
$bot = new Bot('TOKEN');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$keyboard = ikb([
		[ ['See phgram on GitHub!', 'https://github.com/usernein/phgram', 'url'] ]
	]);
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello, world!', 'reply_markup' => $keyboard]);
}