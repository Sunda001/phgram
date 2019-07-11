<?php
class MethodResult extends ArrayObj {
	public $json = '[]';
	private $bot = null;

	public function __construct($json, $bot) {
		global $lastResult;
		$this->json = $json;
		$data = json_decode($json);
		parent::__construct($data);
		$lastResult = $this;
		$this->bot = $bot;
	}
	
	public function __get($index) {
		return $this->data[$index] ?? $this->data['result'][$index] ?? NULL;
	}
	
	public function __isset($key) {
		return isset($this->data[$key]) || isset($this->data['result'][$key]);
	}
	
	public function __set($key, $val) {
		if (isset($this->data['result'][$key])) {
			$this->data['result'][$key] = $val;
		} else {
			$this->data[$key] = $val;
		}
	}
	
	public function __unset($key) {
		if (isset($this->data['result'][$key])) {
			unset($this->data["result"][$key]);
		} else {
			unset($this->data[$key]);
		}
	}
	
	##### Functions implemented by ArrayAccess #####
	public function offsetGet($index) {
		return $this->data[$index] ?? $this->data['result'][$index] ?? null;
	}

	public function offsetSet($index, $value) {
		if (isset($this->data['result'][$index])) {
			$this->data['result'][$index] = $value;
		} else {
			$this->data[$index] = $value;
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
	
	# shortcuts
	public function edit($text, $params = []) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['chat_id' => $this->chat->id, 'disable_web_page_preview' => TRUE, 'text' => $text, 'message_id' => $this->message_id];
		
		if ($params != []) {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			$default['text'] = $text;
		}
		$call = $this->bot->editMessageText($default);
		$this->__construct($call->json, $this->bot);
		return $call;
	}
	
	public function append($text, $params = []) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['chat_id' => $this->chat->id, 'disable_web_page_preview' => TRUE, 'text' => $text, 'message_id' => $this->message_id];
		
		if ($params != []) {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
		}
		$default['text'] = $this->text.$text;
		$call = $this->bot->editMessageText($default);
		$this->__construct($call->json, $this->bot);
		return $call;
	}
	
	public function reply($text, $params = []) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['chat_id' => $this->chat->id, 'disable_web_page_preview' => TRUE, 'text' => $text, 'reply_to_message_id' => $this->message_id];
		if ($params == []) {
			return $this->bot->sendMessage($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			$default['text'] = $text;
			return $this->bot->sendMessage($default);
		}
	}
	
	public function delete($params = []) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['chat_id' => $this->chat->id, 'message_id' => $this->message_id];
		if ($params == []) {
			return $this->bot->deleteMessage($default);
		} else {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
			return $this->bot->deleteMessage($default);
		}
	}
	
	public function forward($chat_id, $params = []) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['from_chat_id' => $this->chat->id, 'chat_id' => $chat_id, 'message_id' => $this->message_id];
		if ($params != []) {
			foreach ($params as $param => $value) {
				$default[$param] = $value;
			}
		}
		$result = [];
		if (is_array($chat_id)) {
			foreach ($chat_id as $id) {
				$default['chat_id'] = $id;
				$result[] = $this->bot->forwardMessage($default);
			}
		}
		if (count($result) == 1) {
			return $result[0];
		} else {
			return $result;
		}
	}
}

$lastResult = NULL;