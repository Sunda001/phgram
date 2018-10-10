<?php
/**
 * Class to help Telegram bot development with PHP.
 *
 * Based on TelegramBotPHP (https://github.com/Eleirbag89/TelegramBotPHP)
 *
 * @author Cezar Pauxis (https://t.me/usernein)
 * @license https://github.com/usernein/phgram/blob/master/LICENSE
*/
class Bot {
	# The bot token
	public $bot_token = '';
	
	# The array of the update
	private $data = [];
	
	# Type of the current update
	private $update_type = '';
	
	# Values for error reporting
	public $debug = FALSE;
	public $debug_admin;
	
	/**
	 * The object constructor.
	 *
	 * The only required parameter is $bot_token. Pass a chat id as second argument to enable error reporting.\
	 *
	 * @param string $bot_token The bot token
	 * @param $debug_chat Chatbid which the errors will be sent to
	 */
	public function __construct(string $bot_token, $debug_chat = FALSE) {
		$this->bot_token = $bot_token;
		$this->data = $this->getData();
		$this->update_type = @array_keys($this->data)[1];
		if ($debug_chat) {
			$this->debug_admin = $debug_chat;
			$this->debug = TRUE;
		}
	}
	
	
	/**
	 * Makes the request to BotAPI.
	 *
	 * @param string $url URl of api, already including the method name
	 * @param array $content Associative array of arguments
	 *
	 * @return string
	 */
	private function sendAPIRequest(string $url, array $content) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	
	/**
	 * Responds directly the webhook with a method and its arguments
	 *
	 * @param string $method Method name
	 * @param array $arguments Associative array with arguments
	 *
	 * @return void
	 */
	public function respondWebhook(string $method, array $arguments) {
		header("Content-Type: application/json");
		$arguments['method'] = $method;
		echo json_encode($arguments);
	}
	
	
	/**
	 * Handle calls of unexistent methods. i.e. BotAPI methods, which aren't set on this file.
	 *
	 * Every unexistent method and its arguments will be handled by __call() when called. Because of this, methods calls are case-insensitive. 
	 *
	 * @param string $method Method name
	 * @param array $arguments Associative array with arguments
	 *
	 * @return MethodResult
	 */
	public function __call(string $method, array $arguments = NULL) {
		if (!$arguments) $arguments = [[]];
		$url = "https://api.telegram.org/bot{$this->bot_token}/{$method}";
		$reply = $this->sendAPIRequest($url, $arguments[0]);
		
		$reply = new MethodResult($reply);
		if (!$reply['ok'] && $this->debug && error_reporting() > 0) {
			@$this->send(json_encode($reply->data), ['chat_id' => $this->debug_admin]);
		}
		return $reply;
	}
	
	
	/**
	 * Downloads a remote file hosted on Telegram servers to a relative path, by its file_id.
	 *
	 * This function doesn't work with files bigger than 20MB.
	 *
	 * @param string $file_id The file id
	 * @param string $local_file_path A relative path which will be used to save the file. Optional. If omitted, the file will be saved as its file name in the current working directory.
	 *
	 * @return boolean or integer
	 */
	public function download_file(string $file_id, string $local_file_path = NULL) {
		$contents = $this->read_file($file_id);
		if (!$local_file_path) {
			$local_file_path = @$this->getFile(['file_id' => $file_id])->file_name;
		}
		if (!$local_file_path) {
			return false;
		}
		return file_put_contents($local_file_path, $contents);
	}
	
	
	/**
	 * Reads and return the contents of a remote file.
	 *
	 * This function doesn't work with files bigger than 20MB.
	 *
	 * @param string $file_id The file id
	 *
	 * @return string
	 */
	public function read_file(string $file_id) {
		$file_path = $this->getFile(['file_id' => $file_id])->file_path;
		$file_url = "https://api.telegram.org/file/bot{$this->bot_token}/{$file_path}";
		return file_get_contents($file_url);
	}
	
	
	/**
	 * Quick way to send a message.
	 *
	 * Check out the Shortcuts section at the README.
	 *
	 * @param string $text The text to send
	 * @param array $params Associative array with additional parameters to sendMessage. Optional.
	 *
	 * @return MethodResult
	 */
	public function send(string $text, array $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => TRUE, 'text' => $text];
		if ($params == []) {
			return $this->sendMessage($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			return $this->sendMessage($default);
		}
	}
	
	
	/**
	 * Quick way to reply the received message.
	 *
	 * Check out the Shortcuts section at the README.
	 *
	 * @param string $text The text to send
	 * @param array $params Associative array with additional parameters to sendMessage. Optional.
	 *
	 * @return MethodResult
	 */
	public function reply(string $text, array $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => TRUE, 'reply_to_message_id' => $this->MessageID(), 'text' => $text];
		if ($params == []) {
			return $this->sendMessage($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			$default['text'] = $text;
			return $this->sendMessage($default);
		}
	}
	
	
	/**
	 * Quick way to edit a message.
	 *
	 * Check out the Shortcuts section at the README.
	 *
	 * @param string $text The new text for the message
	 * @param array $params Associative array with additional parameters to editMessageText. Optional.
	 *
	 * @return MethodResult
	 */
	public function edit(string $text, array $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => TRUE, 'text' => $text, 'message_id' => $this->MessageID()];
		if ($params == []) {
			return $this->editMessageText($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			$default['text'] = $text;
			return $this->editMessageText($default);
		}
	}
 
	
	/**
	 * Quick way to send a file as document.
	 *
	 * Check out the Shortcuts section at the README.
	 *
	 * @param string $filename The file name
	 * @param array $params Associative array with additional parameters to sendDocument. Optional.
	 *
	 * @return MethodResult
	 */
	public function doc(string $filename, array $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => TRUE];
		$document = curl_file_create($filename);
		if ($document) {
			$this->action("upload_document");
		}
		$default['document'] = $document;
		
		if ($params == []) {
			return $this->sendDocument($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			return $this->sendDocument($default);
		}
	}
	
	
	/**
	 * Quick way to send a ChatAction.
	 *
	 * Check out the Shortcuts section at the README.
	 *
	 * @param string $action The chat action
	 * @param array $params Associative array with additional parameters to sendChatAction. Optional.
	 *
	 * @return MethodResult
	 */
	public function action(string $action = 'typing', array $params = []) {
		$default = ['chat_id' => $this->ChatID(), 'action' => $action];
		if ($params == []) {
			return $this->sendChatAction($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			return $this->sendChatAction($default);
		}
	}
	
	
	/**
	 * Dinamically generates a mention to a user.
	 *
	 * If the user has an username, then it is returned (with '@'). If not, a inline mention is generated using the passed user id and the first name of the user. You can choose the markup language (parse mode) using the second parameter. The default value for it is HTML.
	 *
	 * @param $user_id The user id
	 * @param $parse_mode The parse mode. Optional. The default value is HTML.
	 *
	 * @return string or integer
	 */
	public function mention($user_id, $parse_mode = 'html') {
		$parse_mode = strtolower($parse_mode);
		$info = @$this->Chat($user_id);
		if (!$info) {
			return $user_id;
		}
		$mention = isset($info['username'])? "@{$info['username']}" : ($parse_mode == 'html'? "<a href='tg://user?id={$user_id}'>{$info['first_name']}</a>" : "[{$info['first_name']}](tg://user?id={$user_id})");
		return $mention;
	}
	
	
	/**
	 * Returns the current value for the data (used by data shortcuts)
	 *
	 * Use setData() to overwrite this value
	 *
	 *
	 * @return array
	 */
	public function getData() {
		if (!$this->data) {
			$update_as_json = file_get_contents('php://input') ?: '[]';
			$this->data = json_decode($update_as_json, TRUE);
		}
		
		return $this->data;
	}
 
	
	/**
	 * Overwrites a new data to the object.
	 *
	 * The value set is used by all data shortcuts, getUpdateType() and getData()
	 *
	 * @param array $data The new data
	 *
	 * @return void
	 */
	public function setData(array $data) {
		$this->data = $data;
		$this->update_type = @array_keys($this->data)[1];
	}
	
	
	/**
	 * Returns the current update type.
	 *
	 * It is the second value of the Update object ('message', 'edited_message', 'callback_query', 'inline_query',...).
	 *
	 *
	 * @return string
	 */
	public function getUpdateType() {
		return $this->update_type;
	}
	
	
	/**
	 * Search for a value inside the update.
	 *
	 * The priority is given to the data outside 'message' field. If the value is not found, the second search for the same field will be made inside 'message' (given in callback_query updates). If not found, NULL is returned.
	 *
	 * @param string $search The field to search for
	 *
	 * @return array, integer or string
	 */
	public function getValue(string $search) {
		return $this->data[$this->update_type][$search] ?? $this->data[$this->update_type]['message'][$search] ?? NULL;
	}
	
	
	/**
	 * Checks if an user is member of the specified chat.
	 *
	 * @param int $user_id The user is
	 * @param $chat_id The chat id
	 *
	 * @return boolean
	 */
	public function in_chat(int $user_id, $chat_id) {
		$member = $this->getChatMember(['chat_id' => $chat_id, 'user_id' => $user_id]);
		if (!$member['ok'] || in_array($member['result']['status'], ['left', 'kicked'])) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	/**
	 * Check if the current chat is a supergroup
	 *
	 *
	 * @return boolean
	 */
	public function is_group() {
		$chat = $this->getValue('chat');
		if (!$chat) return FALSE;
		return ($chat['type'] == 'supergroup') || ($chat['type'] == 'group');
	}
	
	
	/**
	 * Check if the current chat is a supergroup
	 *
	 *
	 * @return boolean
	 */
	public function is_private() {
		$chat = $this->getValue('chat');
		if (!$chat) return FALSE;
		return $chat['type'] == 'private';
	}
	
	
	/**
	 * Check if a user is admin of the specified chat
	 *
	 * Both parameters are optional. The default value for $user_id is the id of the sender and for $chat_id, the current chat id.
	 *
	 * @param $user_id The user id
	 * @param $chat_id The chat id
	 *
	 * @return boolean
	 */
	public function is_admin($user_id = NULL, $chat_id = NULL) {
		if (!$user_id) {
			$user_id = $this->UserID();
		}
		if (!$chat_id) {
			$chat_id = $this->ChatID();
		}
		$member = $this->getChatMember(['chat_id' => $chat_id, 'user_id' => $user_id]);
		return in_array($member['result']['status'], ['administrator', 'creator']);
	}
	
	##### Data shortcuts #####
	public function Text() {
		return $this->getValue('text');
	}
 
	public function ChatID() {
		return $this->getValue('chat')['id'] ?? NULL;
	}

	public function ChatType() {
		return $this->getValue('chat')['type'] ?? NULL;
	}
	
	public function MessageID() {
		return $this->getValue('message_id');
	}
 
	public function Date() {
		return $this->getValue('date');
	}
 
	public function UserID() {
		return $this->getValue('from')['id'] ?? NULL;
	}
 
	public function FirstName() {
		return $this->getValue('from')['first_name'] ?? NULL;
	}
 
	public function LastName() {
		return $this->getValue('from')['last_name'] ?? NULL;
	}
	
	public function Name() {
		$first_name = $this->FirstName();
		$last_name = $this->LastName();
		if ($first_name) {
			$name = $first_name.($last_name? " {$last_name}" : '');
			return $name;
		}
		
		return NULL;
	}
 
	public function Username() {
		return $this->getValue('from')['username'] ?? NULL;
	}
	
	public function Language() {
		return $this->getValue('from')['language_code'] ?? NULL;
	}
 
	public function ReplyToMessage() {
		return $this->getValue('reply_to_message');
	}
 
	public function Caption() {
		return $this->getValue('caption');
	}
	
	public function InlineQuery() {
		return $this->data['inline_query'] ?? NULL;
	}
 
	public function ChosenInlineResult() {
		return $this->data['chosen_inline_result'] ?? NULL;
	}
 
	public function ShippingQuery() {
		return $this->data['shipping_query'] ?? NULL;
	}
 
	public function PreCheckoutQuery() {
		return $this->data['pre_checkout_query'] ?? NULL;
	}
 
	public function CallbackQuery() {
		return $this->data['callback_query'] ?? NULL;
	}
 
	public function Location() {
		return $this->getValue('location');
	}
 
	public function Photo() {
		return $this->getValue('photo');
	}
 
	public function Video() {
		return $this->getValue('video');
	}
 
	public function Document() {
		return $this->getValue('document');
	}
 
	public function UpdateID() {
		return $this->data['update_id'] ?? NULL;
	}
	
	public function ForwardFrom() {
		return $this->getValue('forward_from');
	}
 
	public function ForwardFromChat() {
		return $this->getValue('forward_from_chat');
	}
	
	public function Entities() {
		return $this->getValue('entities') ?? $this->getValue('caption_entities');
	}
	##### ####### #####
	
	/**
	 * Returns a Chat object as array.
	 *
	 * Also a shortcut for getChat().
	 *
	 * @param $chat_id The chat id
	 *
	 * @return array or null
	 */
	public function Chat($chat_id = NULL) {
		if (!$chat_id) {
			$chat_id = $this->ChatID();
		}
		$chat = $this->getChat(['chat_id' => $chat_id]);
		if ($chat['ok']) {
			return $chat['result'];
		}
		return FALSE;
	}
}

# A MethodResult instance holding the last method result
$lastResult = NULL;

/**
 * Class that holds the result of every method call made with phgram
 *
 * @author Cezar Pauxis (https://t.me/usernein)
*/ 
class MethodResult implements ArrayAccess {
	public $data = [];
	public $json = '[]';
	public $ok, $result, $description, $error_code, $parameters;
	
	/**
	 * The object constructor.
	 *
	 * @param string The method result as JSON.
	 */
	public function __construct(string $json_result, bool $register_last_result = TRUE) {
		global $lastResult;
		$this->json = $json_result;
		$this->data = json_decode($json_result, TRUE);
		
		$data = json_decode($json_result);
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}
		if ($register_last_result == TRUE) {
			$lastResult = new MethodResult($json_result, FALSE);
		}
	}
	
	/**
	 * Cast the object into a string
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode($this->data);
	}
	
	/**
	 * Magic method which helps to get values from the result data
	 *
	 * @return array, integer or string
	 */
	public function __get($index) {
		return $this->$index ?? $this->result->$index ?? NULL;
	}
	
	##### Functions implemented by ArrayAccess #####
	public function offsetGet($index) {
		return $this->data[$index] ?? $this->data['result'][$index] ?? NULL;
	}
	public function offsetSet($index, $value) {
		if (isset($this->data[$index])) {
			$this->data[$index] = $value;
		} else if (isset($this->data['result'][$index])) {
			$this->data['result'][$index] = $value;
		} else {
			$this->$index = $value;
		}
	}
	public function offsetExists($index) {
		return (isset($this->data[$index]) || isset($this->data['result'][$index]));
	}
	public function offsetUnset($index) {
		if (isset($this->data[$index])) {
			unset($this->data[$index]);
		} else if (isset($this->data['result'][$index])) {
			unset($this->data['result'][$index]);
		} else {
			unset($this->$index);
		}
	}
	##### ####### #####
}

/**
 * Build an InlineKeyboardMarkup object, as JSON.
 *
 * @param array $options Array of lines. Each line is a array with buttons, that also are arrays. Check the documentation for examples.
 *
 * @return string
 */
function ikb(array $options) {
	$lines = [];
	foreach ($options as $line_pos => $line_buttons) {
		$lines[$line_pos] = [];
		foreach ($line_buttons as $button_pos => $button) {
			$lines[$line_pos][$button_pos] = btn(...$button);
		}
	}
	$replyMarkup = [
		'inline_keyboard' => $lines,
	];
	return json_encode($replyMarkup, 480);
}

/**
 * Build an InlineKeyboardButton object, as array.
 *
 * The type can be omitted. Passing two parameters (text and value), the type will be assumed as 'callback_data'.
 *
 * @param string $text Text to show in the button.
 * @param string $param Value which the button will use, depending of $type.
 * @param string $type Type of button. Optional. The default value is 'callback_data'.
 *
 * @return array
 */
function btn($text, string $value, string $type = 'callback_data') {
	return ['text' => $text, $type => $value];
}

 
/**
 * Build a ReplyKeyboardMarkup object, as JSON.
 * 
 * @param array $options Array of lines. Each line is a array with buttons, that can be arrays generated by kbtn() or strings. Check the documentation for examples.
 * @param boolean $resize_keyboard If TRUE, the keyboard will allow user's client to resize it. Optional. The default value is FALSE.
 * @param boolean $one_time_keyboard If TRUE, the keyboard will be closed after using a button. Optional. The default value is FALSE.
 * @param boolean $selective If TRUE, the keyboard will appear only to certain users. Optional. The default value is TRUE.
 *
 * @return string
 */
function kb(array $options, bool $resize_keyboard = FALSE, bool $one_time_keyboard = FALSE, bool $selective = TRUE) {
	$replyMarkup = [
		'keyboard' => $options,
		'resize_keyboard' => $resize_keyboard,
		'one_time_keyboard' => $one_time_keyboard,
		'selective' => $selective,
	];
	return json_encode($replyMarkup, 480);
}

/**
 * Build a KeyboardButton object, as array.
 *
 * Is recommended to use only when you need to request contact or location.
 * If you need a simple text button, pass a string instead of KeyboardButton.
 *
 * @param string $text The button text.
 * @param boolean $request_contact Will the button ask for user's phone number? Optional. The default value is FALSE.
 * @param boolean $request_location Will the button ask for user's location? Optional. The default value is FALSE.
 * 
 * @return array
 */
function kbtn($text, bool $request_contact = FALSE, bool $request_location = FALSE) {
	$replyMarkup = [
		'text' => $text,
		'request_contact' => $request_contact,
		'request_location' => $request_location,
	];
	return $replyMarkup;
}

/**
 * Build a RepkyKeyboardRemove object, as JSON.
 *
 * @param boolean $selective If TRUE, the keyboard will disappear only for certain users. Optional. The default value is TRUE.
 *
 * @return string
 */
function hide_kb(bool $selective = TRUE) {
	$replyMarkup = [
		'remove_keyboard' => TRUE,
		'selective' => $selective,
	];
	return json_encode($replyMarkup, 480);
}
 
/**
 * Build a ForceReply object, as JSON.
 *
 * @param boolean $selective If TRUE, the forceReply will affect only to certain users. Optional. The default value is TRUE.
 *
 * @return string
 */
function forceReply(bool $selective = TRUE) {
	$replyMarkup = [
		'force_reply' => TRUE,
		'selective' => $selective,
	];
	return json_encode($replyMarkup, 480);
}