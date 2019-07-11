<?php
class BotErrorHandler {
	public $bot;
	public $admin;
	public $data = [];
	public $show_data;
	
	public function __construct($error_bot, $error_admin, $show_data = true) {
		$this->bot = $error_bot;
		$this->admin = $error_admin;
		$this->show_data = $show_data;
		
		$json = @file_get_contents('php://input');
		$this->data = @json_decode($json, true);
		
		set_error_handler([$this, 'error_handler']);
		set_exception_handler([$this, 'exception_handler']);
	}

	// for restoring the handlers
	public function __destruct () {
		restore_error_handler();
		restore_exception_handler();
	}

	// for calling api methods
	public function __call(string $method, array $args = []) {
		$args = $args[0] ?? [];
		$url = "https://api.telegram.org/bot{$this->bot}/{$method}";
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$result = curl_exec($ch);
		curl_close($ch);
		return @json_decode($result, true);
	}
	
	// returns the error type name by code
	public function get_error_type($code) {
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
		return ($types[$code] ?? 'unknown');
	}
	
	// handle errors
	public function error_handler($error_type, $error_message, $error_file, $error_line, $error_args) {
		if (error_reporting() === 0) return false;
		
		$str = htmlspecialchars("{$error_message} in {$error_file} on line {$error_line}");
		$str .= "\nView:\n". phgram_pretty_debug(2);
		
		if ($this->show_data) {
			$data = $this->data;
			$type = @array_keys($data)[1];
			$data = @array_values($data)[1];
			
			$text = $data['data'] ?? $data['query'] ?? $data['text'] ?? $data['caption'] ?? $data['result_id'] ?? $type;
	
			$sender = $data['from']['id'] ?? null;
			$sender_name = $data['from']['first_name'] ?? null;
	
			$chat = $data['chat'] ?? $data['message']['chat'] ?? null;
			$chat_id = $chat['id'] ?? null;
			$message_id = $data['message_id'] ?? $data['message']['message_id'] ?? null;
			if ($chat['type'] == 'private') {
				$chat_mention = isset($chat['username'])? "@{$chat['username']}" : "<a href='tg://user?id={$sender}'>{$sender_name}</a>";
			} else {
				$chat_mention = isset($chat['username'])? "<a href='t.me/{$chat['username']}/{$message_id}'>@{$chat['username']}</a>" : "<i>{$chat['title']}</i>";
			}
			
			$str .= htmlspecialchars("\n\n\"{$text}\", ").
				($sender? "sent by <a href='tg://user?id={$sender}'>{$sender_name}</a>, " : '').
				($chat? "in {$chat_id} ({$chat_mention})." : '')." Update type: '{$type}'.";
		}
		
		$error_type = $this->get_error_type($error_type);
		$str .= "\nError type: {$error_type}.";
		
		$this->log($str);
		
		return false;
	}
	
	// handle exceptions
	public function exception_handler($e) {
		$str = htmlspecialchars("{$e->getMessage()} in {$e->getFile()} on line {$e->getline()}");
		$str .= "\nView:\n". phgram_pretty_debug(2);
		
		if ($this->show_data) {
			$data = $this->data;
			$type = @array_keys($data)[1];
			$data = @array_values($data)[1];
			
			$text = $data['data'] ?? $data['query'] ?? $data['text'] ?? $data['caption'] ?? $data['result_id'] ?? $type;
	
			$sender = $data['from']['id'] ?? null;
			$sender_name = $data['from']['first_name'] ?? null;
	
			$chat = $data['chat'] ?? $data['message']['chat'] ?? null;
			$chat_id = $chat['id'] ?? null;
			$message_id = $data['message_id'] ?? $data['message']['message_id'] ?? null;
			if ($chat['type'] == 'private') {
				$chat_mention = isset($chat['username'])? "@{$chat['username']}" : "<a href='tg://user?id={$sender}'>{$sender_name}</a>";
			} else {
				$chat_mention = isset($chat['username'])? "<a href='t.me/{$chat['username']}/{$message_id}'>@{$chat['username']}</a>" : "<i>{$chat['title']}</i>";
			}
			
			$str .= htmlspecialchars("\n\n\"{$text}\", ").
				($sender? "sent by <a href='tg://user?id={$sender}'>{$sender_name}</a>, " : '').
				($chat? "in {$chat_id} ({$chat_mention})." : '')." Update type: '{$type}'.";
		}
		
		$this->log($str);
		
		return false;
	}
	
	public function log($text) {
		if (is_array($this->admin)) {
			foreach ($this->admin as $admin) {
				$this->sendMessage(['chat_id' => $admin, 'text' => $text, 'parse_mode' => 'html']);
			}
		} else {
			$this->sendMessage(['chat_id' => $this->admin, 'text' => $text, 'parse_mode' => 'html']);
		}
	}
}