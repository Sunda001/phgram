<?php 
define('BOT_TOKEN', '842773374:AAExTsyurbIJZoqErTqbOouJgm9jkfMKOf0');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
	
// read incoming info and grab the chatID
$content = file_get_contents("php://input");
$update = json_decode($content, true);
$chatID = $update["message"]["chat"]["id"];
		
// compose reply
$reply =  sendMessage();
		
// send reply
$sendto =API_URL."sendmessage?chat_id=".$chatID."&text=".$reply;
file_get_contents($sendto);

function sendMessage(){
$message = "I am a baby bot.";
return $message;
}
