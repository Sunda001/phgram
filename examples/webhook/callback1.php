<?php
# with inline keyboard
include 'phgram.phar';
$bot = new Bot('TOKEN');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$keyboard = ikb([
		[ ['Check phgram on GitHub!', 'https://github.com/usernein/phgram', 'url'] ]
	]);
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello, world!', 'reply_markup' => $keyboard]);
	# or:
	# $bot->send('Hello, world!', ['reply_markup' => $keyboard]);
}