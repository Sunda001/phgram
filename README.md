# phgram

Dinamic, fast and simple framework to develop Telegram Bots with PHP7.
Based on [TelegramBotPHP](https://github.com/Eleirbag89/TelegramBotPHP).

## Requirements
* PHP 7.0 or greater.

## Installing
* Download `bot.class.php` and save it on your project directory.
* Add `require 'bot.class.php';` at the top of your script.

## Examples
### Webhooks
```php
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
}
```
### Long polling
```php
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE');

$offset = 0;
while (true) {
	$updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 300])['result'];
	foreach ($updates as $key => $update) {
		$bot->setData($update);

		$text = $bot->Text();
		$chat_id = $bot->ChatID();

		if ($text == '/start') {
			$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
		}
		
		$offset = $update['update_id']+1;
	}
}
```

## BotAPI Methods
phgram supports all methods and types described on the [official docs](core.telegram.org/bots/api).
Just call any BotAPI method as a class method:
```
$bot->getChat(['chat_id' => '@hpxlist']);
```
```
$bot->getMe();
```

- The result is an instance of `MethodResult`
- All methods are case-insensitive.

## phgram Methods
### Shortcuts
You have a range of method shortcuts. You can use it to make your code clean and easier to read, write and understand.
e.g. instead of:
```
$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hi!', 'parse_mode' => 'HTML', 'disable_web_page_preview' => true]);
```
use:
```
$bot->send('Hi!');
```

All methods accepts 1 or 2 arguments. The first is always the "main" argument, i.e. the most important argument on the method ('text' for sendMessage, 'document' for sendDocument...). The second argument is an optional associative array with custom/additional parameters to use in the method.
Examples:
```
$bot->send('Hey');
```
```
$bot->send('Hey', ['disable_notification' => TRUE]);
```

**P.S.:** Please remember that phgram support ALL methods of BotAPI. The list below is the list of **shortcuts**. You can normally call the methods in the traditional way.

List of available shortcuts:

Name|Method|Default parameters|Note
---|---|---|---
send|sendMessage|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE|The first parameter is the text.
reply|sendMessage|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE, reply\_to\_message\_id=MessageID()|The first parameter is the text.
edit|editMessageText|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE, message\_id=MessageID()|The first parameter is the text.
doc|sendDocument|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE|The first parameter is the relative path to the document to upload.
action|sendChatAction|chat\_id=ChatID(), action=typing|The first parameter, also optional, is the action.

### Special shortcurts:

Name|Description|Parameters|Return|Note
---|---|---|---|---
read\_file|Get the contents of a file|(String, required) file\_id|(String) Content of the file.|This function don't work with files bigger than 20MB.
download\_file|Get the contents of a file and save it into a local file|(String, required) file\_id, (String, optional) local\_file\_path|If the download was successful, returns (Integer) size of the file. Otherwise returns FALSE.|This function don't work with files bigger than 20MB. If the second argument is omitted, then the file is saved with its original name in the current working directory.
mention|Generate a mention to a user\_id. If the user has username, it is returned. If not, a HTML/Markdown mention hyperlink is returned.|(Integer, required) user\_id, (String, optional) parse\_mode|(String) A mention to the user.|In case of errors, the passed id is returned. The default parse mode is HTML.
in\_chat|Check if a user is in the specified chat.|(Integer, required) user\_id, (Mixed, required) chat\_id|(Bool)|
is\_admin|Check if a user is an administrator of the specified chat.|(Integer, optional) user\_id, (Mixed, optional) chat\_id|(Bool)|The default value of the first and second parameters are, respectively, the current user id and chat id.
is\_group|Check if the current chat is a supergroup.|(void)|(Bool)|
is\_private|Check if the current chat is a private conversation.|(void)|(Bool)|
respondWebhook|Prints out the method and arguments as response to webhook.|(String, required) method, (Array, required) arguments)|(void)|
Chat|Returns the Chat object of the specified Chat id|(Mixed, optional) the chat id|(Array) the Chat object|If the first argument is omitted or NULL, it will return the Chat object of the current chat

### Data shortcuts
Instead of use things like:
```
<?php
$content = file_get_contents('php://input');
$update = json_decode($content, true);
$text = $update['message']['text'];
$chat_id = $update['message']['chat']['id'];
$user_id = $update['message']['from']['id'];
$message_id = $update['message']['message_id'];
```
phgram gives you simple and dinamic method to get these values:
```
<?php
include 'bot.class.php';
$bot = new Bot('TOKEN:HERE');

$text = $bot->Text();
$chat_id = $bot->ChatID();
$user_id = $bot->UserID();
$message_id = $bot->MessageID();
```
Doesn't really matter what the update is of. If the value exists, it is returned. If not, NULL is returned.

There are some special cases, as with calback\_query, that has 'message' field. The internal function 'getValue' will always search the value firstly outside 'message' field. If not found, it will search a correspondent value in inside 'message' field of callback\_query.

e.g. in a callback\_query update, Text() will return the text of the message with the clicked button. However UserID() will return the id of the user that clicked the button, because callback\_query has a 'from' field and it gains priority over the 'from' inside 'message'.

This is the complete list of data shortcuts methods:
* Text()
* ChatID()
* ChatType()
* MessageID()
* Date()
* UserID()
* FirstName()
* LastName()
* Name() _(FirstName + LastName)_
* Username() _(without '@')_
* Language()
* ReplyToMessage()
* Caption()
* InlineQuery()
* ChosenInlineResult()
* ShippingQuery()
* PreCheckoutQuery()
* CallbackQuery()
* Location()
* Photo()
* Video()
* Document()
* Entities()
* UpdateID()
* ForwardFrom()
* ForwardFromChat()

### Utilities

Name|Description|Parameters|Note
---|---|---|---
getData|Returns the update as an associative array.|(void)|
getUpdateType|Returns a string with the update type ('message', 'channel_post', 'callback_query')|(void)|
setData|Writes a new value to the saved data (the return of getData())|(Array, required) The new data|The value of getUpdateType()

### Reply\_markup functions
Note that these items below are **functions**, **not methods**.
These functions are used to generate JSON for reply\_markup parameter in some methods.

#### ikb
Use this function to generate a InlineKeyBoardMarkup object. Syntax:
```
$options = [
	[ ['text', 'data', 'callback_data'] ]
];
$keyboard = ikb($options);
```
$options is a array of lines (also arrays). Each line contains at least one button (also an array).
The button array may contain 2 or 3 elements, depending of the button type.
Syntax:
```
[TEXT, VALUE, TYPE]
```
TEXT is the value that will be shown to the user.
VALUE is the data of the button.
TYPE is the type of the button (callback_data, url...). If TYPE is 'callback\_data', VALUE will be used as callback data. If TYPE is 'url', the VALUE will be the url which the button will link to, and so on.

_Note:_ Only TEXT and VALUE are required. The default value of TYPE is callback\_data.
So, for callback buttons, you can ommit the TYPE parameters. Check this example:
```
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE');
$options = [
	[ ['Callback 1', 'callback 1'], ['Callback 2', 'callback 2'] ],
	['URl 1', 't.me/usernein', 'url'], ['URl 2', 't.me/hpxlist', 'url'] ],
	['Inline 1', 'aleatory query', 'switch_inline_query'], ['Inline 2', 'another query', 'switch_inline_query_current_chat'] ]
];
$keyboard = ikb($options);
$bot->send('Testing', ['reply_markup' => $keyboard]);
```
Obviously you are free to edit, remove and add your own buttons. You can mix them, use emojis, apply your own format, and many cool things.

#### btn
This function is used by `ikb()` to generate a InlineKeyboardButton object, so you probably will not use it.

Syntax:
```
btn( TEXT, VALUE, TYPE='callback_data' )
```

The result is a InlineKeyboardButton object as array.

#### kb
Use this function to generate a ReplyKeyboardMarkup objects (also JSON-encoded).

The first parameter is required and the other 3 are optional.

Parameters:

Name|Required|Type|Default value|Description
---|---|---|---|---
$options|Yes|Array||An array with the same lines structure of `ikb()`, but different buttons structure.
$resize_keyboard|No|Boolean|FALSE|_Requests clients to resize the keyboard vertically for optimal fit (e.g., make the keyboard smaller if there are just two rows of buttons)._
$one_time_keyboard|No|Boolean|FALSE|_Requests clients to hide the keyboard as soon as it's been used._
$selective|No|Boolean|TRUE|_Use this parameter if you want to show the keyboard to specific users only._

$options is a array of lines. Each line might contain KeyboardButton objects (as array) (generated by `kbtn()`) or for simple buttons, that doesn't request contact nor location, simple strings.

Basic example:
```
$options = [
	[ 'Button 1', 'Button 2' ],
	[ 'Button 3' ]
];

$keyboard = kb($options, TRUE);
$bot->send('Testing...', ['reply_markup' => $keyboard]);
```
More info at [docs](https://core.telegram.org/bots/api#replykeyboardmarkup).

#### kbtn
Use this function to generate a KeyboardButton object (as array, to use inside $options of `kb()`)

Parameters:

Name|Required|Type|Default value|Description
---|---|---|---|---
$text|Yes|String||_Text of the button. If none of the optional fields are used, it will be sent as a message when the button is pressed._
$request_contact|No|Boolean|FALSE|_If True, the user's phone number will be sent as a contact when the button is pressed. Available in private chats only_
$request_location|No|Boolean|FALSE|_If True, the user's current location will be sent when the button is pressed. Available in private chats only_

#### hide_kb
Use this function to generate a ReplyKeyboardRemove object (JSON-encoded).

The only parameter is the boolean $selective, that is optional. The default value is TRUE.

#### forceReply
Use this function to generate a ForceReply object (JSON-encoded).

The only parameter is the boolean $selective, that is optional. The default value is TRUE.

## Handling errors
A method call might fail sometimes. To get reports of these errors, you may pass a chat id as second parameter to the Bot class constructor. Example:
```
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE', 276145711);
```
The bot will send the JSON-encoded response of the unsuccessful call. Example:
_{"ok":false,"error_code":400,"description":"Bad Request: message is not modified"}_

To disable/enable error reporting, you can overwrite the `$debug` attribute with TRUE or FALSE. Set it to FALSE to disable and to TRUE to enable.

To disable error reporting for a single method, put '@' before the call:
```
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE', 276145711);
$bot->debug = FALSE; # disabled
$bot->debug = TRUE; # enabled again

$bot->send('<Hey!'); # HTML is the default reply_markup, so this call will report to you a HTML error.
@$bot->send('<Hey!'); # Quiet error
```
If you set the PHP value 'error_reporting' to 0, you will not receive these reports.

You can also set a new value for `$debug_admin` attribute, to tell the script where should it send the reports.
```
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE', 276145711);
$bot->debug_admin = 204807919; # not me anymore :(
$bot->debug_admin = 276145711; # me again :)
$bot->debug_admin = 200097591; # changed again.
$bot->debug = FALSE; # nobody
```

## MethodResult
Every method called by phgram (even shortcuts) returns to you a MethodResult instance.
This doesn't affect you or your code. Since it implements ArrayAccess, you can access its values by ['indexes']. But remember that it is also an object, so you can also access its values as $object->attributes.

The coolest thing here is that you don't need to pass through 'result' to access its values:
```
<?php
require 'bot.class.php';
$bot = new Bot('TOKEN:HERE');

$result = $bot->send('Hey!');
echo $result['result']['message_id'] ."\n"; # this works!
echo $result->result->message_id ."\n"; # this also works!
echo $result['message_id'] ."\n"; # yeah, it works!
echo $result->message_id ."\n"; # why it shouldn't? :)
```