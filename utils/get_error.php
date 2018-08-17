<?php
/*
+ Author: t.me/usernein
+ Include this file in your code and be happy :)
* Example:
include 'get_error.php';
$error_bot = '501707370:AAE23NOEnjwsuKBwZilFLRDTETqKarwKBxU';
$error_admin = 276145711;

* The bot.class.php should be on the same directory of this file
*/

$errot_bot = 'TOKEN';
$error_admin = 276145711;

// returns the error type name by code
function get_error_type ($code) {
	$types = [
		E_ERROR => 'E_ERROR',
		E_WARNING => 'E_WARNING',
		E_PARSE => 'E_PARSE',
		E_NOTICE => 'E_NOTICE',
		E_CORE_ERROR => 'E_CORE_ERROR',
		E_CORE_WARNING => 'E_CORE_WARNING',
		E_COMPILE_ERROR => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR => 'E_USER_ERROR',
		E_USER_WARNING => 'E_USER_WARNING',
		E_USER_NOTICE => 'E_USER_NOTICE',
		E_STRICT => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED => 'E_DEPRECATED',
		E_USER_DEPRECATED => 'E_USER_DEPRECATED',
	];
	return ($types[$code] ?: "unknown");
}

// handle errors
function getError($error_type, $error_message, $error_file, $error_line) {
	if (error_reporting() === 0) return true;
	
	global $error_admin, $error_bot;
	require_once 'bot.class.php';
	$bot = new Bot($error_bot);
	$where = $error_file; // origins
	$text = $bot->CallbackQuery()['data'] ?? $bot->InlineQuery()['query'] ?? $bot->Text() ?? $bot->Caption() ?? $bot->ChosenInlineResult()['result_id'] ?? $bot->getUpdateType();
	$error_type = get_error_type($error_type);
		
	$str = htmlspecialchars("{$error_message} in $where on line {$error_line}

\"{$text}\", ").($bot->UserID()? "sent by <a href='tg://user?id={$bot->UserID()}'>{$bot->FirstName()}</a>, " : '').
($bot->ChatID()? "in {$bot->ChatID()}." : "id: {$bot->ChosenInlineResult()['inline_message_id']}.")." Update type: '{$bot->getUpdateType()}'.
Error type: {$error_type}.";
	$bot->sendMessage(['chat_id' => $error_admin, 'text' => $str, 'parse_mode' => 'html']);
}

// handle exceptions
function exception_handler($e) {
	global $error_admin, $error_bot;
	require_once 'bot.class.php';
	$bot = new Bot($error_bot);
	$where = $e->getFile(); // origins
	$text = $bot->CallbackQuery()['data'] ?? $bot->InlineQuery()['query'] ?? $bot->Text() ?? $bot->Caption() ?? $bot->ChosenInlineResult()['result_id'] ?? $bot->getUpdateType();
	
	$str = htmlspecialchars("{$e->getMessage()} in $where on line {$e->getLine()}

\"{$text}\", ").($bot->UserID()? "sent by <a href='tg://user?id={$bot->UserID()}'>{$bot->FirstName()}</a>, " : '').
($bot->ChatID()? "in {$bot->ChatID()}." : "id: {$bot->ChosenInlineResult}.")." Update type: '{$bot->getUpdateType()}'.";

	$bot->sendMessage(['chat_id' => $error_admin, 'text' => $str, 'parse_mode' => 'html']);
}
set_exception_handler('exception_handler');
set_error_handler('getError');