<?php
class Bot {
	# Important variables
	## Bot token
	public $bot_token = '';
	## Update array
	private $data = [];
	## Updates (if using getUpdates)
	private $updates = [];
	
	# Class core
	/* Constructor:
		- Use: $bot = new Bot('TOKEN');
		- Change the TOKEN to yours
	*/
	public function __construct($bot_token) {
		$this->bot_token = $bot_token;
		$this->data = $this->getData();
	}
	
	# API core (to make the requests)
	private function sendAPIRequest($url, array $content) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	/* Respond directely to webhook with a method and it parameters
		- Use: $bot->respondWebhook('sendMessage', ['chat_id' => $chat_id, 'text' => 'If you use other variable name for chat_id and bot object, use them']);
	*/
	public function respondWebhook($method, $args) {
		header("Content-Type: application/json");
		$args['method'] = $method;
		echo json_encode($args);
	}
	
	/* Calls the API methods
		- Examples:
			- $bot->getMe();
			- $bot->sendChatAction(['chat_id' => $chat_id, 'action' => 'typing']);
	*/
	public function __call($method, $args = [[]]) {
		$url = "https://api.telegram.org/bot{$this->bot_token}/{$method}";
		$reply = $this->sendAPIRequest($url, $args[0]);
		
		return json_decode($reply, true);
	}
	
	# API utilites
	/* Downloads a file to server
		- Use: $bot->download_file($file_id); # won't change the file name
		- or: $bot->download_file($file_id, 'file.txt'); # will save as file.txt
	*/
	public function download_file($file_id, $local_file_path = null) {
		$telegram_file_info = $this->getFile(['file_id' => $file_id])['result'];
		$telegram_file_path = $telegram_file_info['file_path'];
		if (!$local_file_path)
			$local_file_path = $telegram_file_info['file_name'];
		$file_url = "https://api.telegram.org/file/bot{$this->bot_token}/{$telegram_file_path}";
		$in = fopen($file_url, 'rb');
		$out = fopen($local_file_path, 'wb');

		while ($chunk = fread($in, 8192)) {
			fwrite($out, $chunk, 8192);
		}
		fclose($in);
		fclose($out);
		
		return ['filename' => basename($local_file_path), 'filepath' => $local_file_path, 'filesize' => filesize($local_file_path)];
	}
	## Looks for a message to reply. The priority goes to any replied message. Otherwise, the message_id of current update is returned.
	public function reply_to () {
		if ($this->ReplyToMessage()) {
			return $this->ReplyToMessage()['message_id'];
		}
		return $this->MessageID();
	}
	
	# Technical utilities
	## Use to answer the webhook with status 200
	public function respondSuccess() {
		http_response_code(200);
		return json_encode(['status' => 'success']);
	}
	
	# Conditions shortcuts
	## Check if the user is in the specified chat
	public function in_chat($user_id, $chat_id) {
		$member = $this->getChatMember(['chat_id' => $chat_id, 'user_id' => $user_id]);
		if (!$member['ok'] or in_array($member['status'], ['left', 'restricted', 'kicked'])) {
			return false;
		}
		
		return true;
	}
	## Check if the chat is a supergroup
	public function is_group() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['chat'])) {
			return ($this->data[$type]['chat']['type'] == 'supergroup');
		} else if (isset($this->data[$type]['message']['chat'])) {
			return ($this->data[$type]['message']['chat']['type'] == 'supergroup');
		}
		return false;
	}
	## Check if the chat is a private chat
	public function is_private() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['chat'])) {
			return ($this->data[$type]['chat']['type'] == 'private');
		} else if (isset($this->data[$type]['message']['chat'])) {
			return ($this->data[$type]['message']['chat']['type'] == 'private');
		}
		return false;
	} 
	
	# Methods shortcuts
	/* Replies the message specified by reply_to().
		- Requires only one parameter, the text to send. All other values are automatically added.
		- Default values:
			- chat_id = current chat
			- parse_mode = HTML
			- disable_web_page_preview = true
			- reply_to_message_id = a replied message or the message received
		- You can change the default values passing an associative array as second parameter.
	*/
	public function reply($text, $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true, 'reply_to_message_id' => $this->reply_to()];
		foreach ($params as $param => $value) {
			$default[$param] = $value;
		}
		$default['text'] = $text;
		return $this->sendMessage($default);
	}
	/* Quickly sends a message to current chat, with parse_mode set to HTML and disable_web_page_preview set to true.
		- See reply()
	*/
	public function send($text, $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
		foreach ($params as $param => $value) {
			$default[$param] = $value;
		}
		$default['text'] = $text;
		return $this->sendMessage($default);
	}
	/* Quickly edits a message.  Have the same default paramenters of send()
		- Requires two parameters: the new text and the message_id of message to edit
	*/
	public function edit($text, $message_id, $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
		foreach ($params as $param => $value) {
			$default[$param] = $value;
		}
		$default['text'] = $text;
		$default['message_id'] = $message_id;
		return $this->editMessageText($default);
	}
	/* Quickly uploads a local file. Have the same default paramenters of send()
		- Requires only the file path (relative).
	*/
	public function doc($filename, $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
		foreach ($params as $param => $value) {
			$default[$param] = $value;
		}
		$document = curl_file_create (realpath($filename));
		if ($document) $this->action("upload_document");
		$default['document'] = $document;
		return $this->sendDocument($default);
	}
	/* Quickly sends a chat action. Have the same default paramenters of send()
		- Requires no parameters, defaults to 'typing'.
	*/
	public function action($action = 'typing', $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'action' => $action];
		foreach ($params as $param => $value) {
			$default[$param] = $value;
		}
		return $this->sendChatAction($default);
	}
	
	# Data shortcuts
	## Returns an associative, multi-dimensional array with update values
	public function getData() {
		if ($this->data == []) {
			$rawData = file_get_contents('php://input');
			return json_decode($rawData, true);
		} else {
			return $this->data;
		}
	}
	## Set the current update values
	public function setData(array $data) {
		$this->data = $data;
	}
	
	# Basic info
	/* You can use any method below to quickly gets a value without worrying on update type
		- $bot->Text() will return the message text even if the update is a channel_post, normal message or even callback_query (in this case, will return the text of the message with the button)
		- i.e. any update that contains 'text' field.
		- If the update has not 'text', the method will return NULL
	*/
	public function Text() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['text'])) {
			return $this->data[$type]['text'];
		} else if (isset($this->data[$type]['message']['text'])) {
			return $this->data[$type]['message']['text'];
		}
		
		return null;
	}
	public function ChatID() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['chat']['id'])) {
			return $this->data[$type]['chat']['id'];
		} else if (isset($this->data[$type]['message']['chat']['id'])) {
			return $this->data[$type]['message']['chat']['id'];
		}
		
		return null;
	}
	public function MessageID() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['message_id'])) {
			return $this->data[$type]['message_id'];
		} else if (isset($this->data[$type]['message']['message_id'])) {
			return $this->data[$type]['message']['message_id'];
		}
		
		return null;
	}
	public function Date() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['date'])) {
			return $this->data[$type]['date'];
		} else if (isset($this->data[$type]['message']['date'])) {
			return $this->data[$type]['message']['date'];
		}
		
		return null;
	}
	public function UserID() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['from']['id'])) {
			return $this->data[$type]['from']['id'];
		} else if (isset($this->data[$type]['message']['from']['id'])) {
			return $this->data[$type]['message']['from']['id'];
		}
		
		return null;
	}
	public function FirstName() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['from']['first_name'])) {
			return $this->data[$type]['from']['first_name'];
		} else if (isset($this->data[$type]['message']['from']['first_name'])) {
			return $this->data[$type]['message']['from']['first_name'];
		}
		
		return null;
	}
	public function LastName() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['from']['last_name'])) {
			return $this->data[$type]['from']['last_name'];
		} else if (isset($this->data[$type]['message']['from']['last_name'])) {
			return $this->data[$type]['message']['from']['last_name'];
		}
		
		return null;
	}
	public function Username() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['from']['username'])) {
			return $this->data[$type]['from']['username'];
		} else if (isset($this->data[$type]['message']['from']['username'])) {
			return $this->data[$type]['message']['from']['username'];
		}
		
		return null;
	}
	public function ReplyToMessage() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['reply_to_message'])) {
			return $this->data[$type]['reply_to_message'];
		} else if (isset($this->data[$type]['message']['reply_to_message'])) {
			return $this->data[$type]['message']['reply_to_message'];
		}
		
		return null;
	}
	public function Caption() {
		$type = $this->getUpdateType();
		if (isset($this->data[$type]['caption'])) {
			return $this->data[$type]['caption'];
		} else if (isset($this->data[$type]['message']['caption'])) {
			return $this->data[$type]['message']['caption'];
		}
		
		return null;
	}
	
	# Types
	public function InlineQuery() {
		return $this->data['inline_query'] ?? null;
	}
	public function ChosenInlineResult() {
		return $this->data['chosen_inline_result'] ?? null;
	}
	public function ShippingQuery() {
		return $this->data['shipping_query'] ?? null;
	}
	public function PreCheckoutQuery() {
		return $this->data['pre_checkout_query'] ?? null;
	}
	public function CallbackQuery() {
		return $this->data['callback_query'] ?? null;
	}
	public function Location() {
		$type = $this->getUpdateType();
		return $this->data[$type]['location'] ?? null;
	}
	public function Photo() {
		$type = $this->getUpdateType();
		return $this->data[$type]['photo'] ?? null;
	}
	public function Video() {
		$type = $this->getUpdateType();
		return $this->data[$type]['video'] ?? null;
	}
	public function Document() {
		$type = $this->getUpdateType();
		return $this->data[$type]['document'] ?? null;
	}
	
	# Update info
	public function UpdateID() {
		return $this->data['update_id'] ?? null;
	}
	public function getUpdateType() {
		return @array_keys($this->data)[1];
	}
	
	# Forward info
	public function ForwardFrom() {
		$type = $this->getUpdateType();
		return $this->data[$type]['forward_from'] ?? null;
	}
	public function ForwardFromChat() {
		$type = $this->getUpdateType();
		return $this->data[$type]['forward_from_chat'] ?? null;
	}
	
	# Chat info
	## Shortcut to getChat. You can pass a chat_id or @channel_username.
	public function Chat($chat_id = null) {
		if (!$chat_id) {
			$chat_id = $this->ChatID();
		}
		$chat = $this->getChat(['chat_id' => $chat_id]);
		if ($chat['ok']) {
			return $chat['result'];
		}
		return false;
	}
}

# Keyboard creators
## keyboard
function kb(array $options, $onetime = false, $resize = false, $selective = true) {
	$replyMarkup = [
		'keyboard'		=> $options,
		'one_time_keyboard' => $onetime,
		'resize_keyboard'   => $resize,
		'selective'		=> $selective,
	];
	$encodedMarkup = json_encode($replyMarkup, true);
	return $encodedMarkup;
}
## keyboard button
function kbtn($text, $request_contact = false, $request_location = false) {
	$replyMarkup = [
		'text'			=> $text,
		'request_contact'  => $request_contact,
		'request_location' => $request_location,
	];
	return $replyMarkup;
}

/* Inline keyboard creator
	- Use:
$options = [
	[ ['Button1', 'callback data here'] ],
	[ ['Button2', 'url', 't.me/usernein'] ],
	[ ['Button3', 'data3'], ['Button4', 'data4'] ],
];
$keyboard = ikb($options); # ready to pass to any method
*/
function ikb(array $options) {
	$new = [];
	foreach ($options as $line_pos => $line_buttons) {
		$new[$line_pos] = [];
		foreach ($line_buttons as $button_pos => $button) {
			$new[$line_pos][$button_pos] = btn(...$button);
		}
	}
	$replyMarkup = [
		'inline_keyboard' => $new,
	];
	$encodedMarkup = json_encode($replyMarkup, true);
	return $encodedMarkup;
}
## button (inline)
function btn ($text, $type, $param = null) {
	if ($param == null) {
		$param = $type;
		$type = 'callback_data';
	}
	return ['text' => $text, $type => $param];
}
## hide keyboard
function hide_kb($selective = true) {
	$replyMarkup = [
		'remove_keyboard' => true,
		'selective'	=> $selective,
	];
	$encodedMarkup = json_encode($replyMarkup, true);
	return $encodedMarkup;
}
function forceReply($selective = true) {
	$replyMarkup = [
		'force_reply' => true,
		'selective'   => $selective,
	];
	$encodedMarkup = json_encode($replyMarkup, true);
	return $encodedMarkup;
}