# phgram
Uma classe feita com amor e PHP para ajudar no desenvolvimento de bots no Telegram.
Desenvolvida com base na [TelegramBotPHP](https://github.com/Eleirbag89/TelegramBotPHP).

## Requerimentos
* PHP 7.1 ou superior.

## Instalação

* Baixe e salve o `bot.class.php` na mesma pasta que o script do bot.
* Adicione `include 'bot.class.php';` ao início do código e tenha tudo em mãos.

## Exemplos
### Webhooks
```
<?php
include 'bot.class.php';
$bot = new Bot('TOKEN');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
	$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Olá, mundo!']);
}
```
### Long polling (getUpdates)
```
<?php
include 'bot.class.php';
$bot = new Bot('TOKEN');
# set_time_limit(0); # cuidado ao usar

$offset = 0;

while (true) {
	$updates = $bot->getUpdates(['offset' => $offset])['result'];
	foreach ($updates as $key => $update) {
		$bot->setData($update); # preenche os dados de update

		$text = $bot->Text();
		$chat_id = $bot->ChatID();

		if ($text == '/start') {
			$bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Olá, mundo!']);
		}
	}
	$offset = $update['update_id']+1;
}
```

# Métodos

Esta classe suporta todos os métodos e tipos da API de Bots do Telegram, atualizada.
Basta chamar qualquer método passando os argumentos como uma array associativa (exceto quando o método não requer argumentos).
Ex:
    `$bot->getChat(['chat_id' => '@hpxlist']);`
    `$bot->getMe();`

- O resultado é uma array associativa, gerada do JSON retornado pela API.
- Os métodos, parâmetros e argumentos são todos _case-insentitive_.

## Métodos especiais
Alguns atalhos aos métodos e à outras informações foram adicionados:

### Atalhos para métodos

##### download_file
- Use para salvar um arquivo localmente.
- Parâmetros:
    - file\_id : file\_id do arquivo no Telegram
    - local\_path : Opcional. Caminho relativo para salvar o arquivo. Qualquer arquivo com o mesmo nome será sobrescrito.
- Retorno: array associativa contendo 3 informações sobre o arquivo salvo.
    - filename : nome do arquivo salvo.
    - filepath : caminho do arquivo salvo.
    - filesize : tamanho (em Bytes) do arquivo salvo.

##### send
- Use para enviar rapidamente uma mensagem.
    É nada mais que um atalho para `sendMessage`, com alguns valores padrões:
      - chat\_id = chat atual
      - parse\_mode = HTML
      - disable\_web\_page\_preview = true
- Parâmetros:
    - text : texto para enviar.
    - arguments : Opcional. array associativa contendo valores para adicionar à chamada.
- Retorno: o mesmo que `sendMessage`

##### reply
- O mesmo que `send`, mas adiciona o valor da função especial `reply_to` como 'reply\_to\_message\_id'.

##### reply_to
- Retorna o message\_id da mensagem respondida (reply\_to\_\message->message\_id) ou o message\_id da mensagem atual.
- Parâmetros: não requer

##### doc
- Use para enviar um arquivo local rapidamente.
    O formato de parâmetros é semelhante ao dos métodos acima, mas o primeiro parâmetro, em vez de texto, é o caminho relativo para o arquivo.
- Retorno: o mesmo que `sendDocument`

##### action
- Envia um 'ChatAction' a um chat. Exemplo: 'escrevendo...'
- Parâmetros:
    - action : Opcional. Nome da ação para enviar. Padrão: typing
    - chat\_id : Opcional. Id do chat para enviar a ação. Padrão: chat atual
- Retorno: o mesmo que `sendChatAction`

##### edit
- Edita uma mensagem.
- Parâmetros:
    - text : texto novo para a mensagem
    - message\_id : mensagem alvo
    - arguments : Opcional. array associativa contendo valores para adicionar à chamada. Padrão: o mesmo que `send`
- Retorno: o mesmo que `editMessageText`

##### Chat
- Retona informação de um chat. Atalho para `getChat`
- Parâmetros:
    - chat\_id : Opcional. Chat alvo. Padrão: chat atual.
- Retorno: o mesmo que `getChat`

##### is_group
- Se o chat atual for um supergrupo, retorna TRUE, caso contrário, FALSE.
- Parâmetros: não requer

##### is_private
- Se o chat atual for um chat privado (usuário-bot), retorna TRUE, caso contrário, FALSE.
- Parâmetros: não requer

##### in_chat
- Use para checar se um usuário está presente em determinado chat.
- Parâmetros:
    - user\_id : id do usuário para buscar.
    - chat\_id : id do chat para buscar o usuário.
- Retorno: valor booleano, que é FALSE caso o usuário não esteja no chat ou o bot não tenha feito a chamada com sucesso. Caso contrário, retorna TRUE.

##### respondWebhook
- Responde com um método diretamente ao webhook.
- Parâmetros:
    - method : método desejado
    - arguments : argumentos do método

### Atalhos para informaçôes
Em vez de ficar usando coisas como:
```
<?php
$content = file_get_contents('php://input');
$update = json_decode($content, true);
$text = $update['message']['text'];
$chat_id = $update['message']['chat']['id'];
$user_id = $update['message']['from']['id'];
$message_id = $update['message']['message_id'];
```
, esta classe oferece formas práticas e dinâmicas para acessar tais valores:
```
<?php
include 'bot.class.php';
$bot = new Bot('token');

$text = $bot->Text();
$chat_id = $bot->ChatID();
$user_id = $bot->UserID();
$message_id = $bot->MessageID();
```
Não interessa se o update é um post, mensagem, edição de post, edição de mensagem, callback\_query, inline\_query, etc. O método buscará por tal valor, e se não achar, retorna NULL.
Veja que em casos de callback\_query, que contém o campo 'message', quando o método não achar um valor como callback\_query->text, ele vai retornar o 'text' do campo 'message' presente.
Em casos que nem o 'text' nem o 'message' estão presentes, como em updates envolvendo consulta inline, o método retornará NULL.

Este são os métodos para obter informações do update:
* Text
* ChatID
* MessageID
* Date
* UserID
* FirstName
* LastName
* Username
* ReplyToMessage
* Caption
* InlineQuery
* ChosenInlineResult
* ShippingQuery
* PreCheckoutQuery
* CallbackQuery
* Location
* Photo
* Video
* Document
* UpdateID
* ForwardFrom
* ForwardFromChat
* Chat (também considerado atalho a um método)
* getUpdateType (retorna o tipo de update, como 'message', 'channel_post', 'chosen_inline_result', etc.
* getData (retorna o update, convertido para array. Use o método setData para alterar o valor do update)

### Funções de criação
Note que são funções e não métodos.
Cada um dos listados acima são usados pelo $bot:
`$bot->send('Olá!');`
`$bot->action();`
E assim com todos.
Os abaixo são funções definidas fora da classe, isto é, são chamadas diretamente:
`ikb(....);`
`forceReply()`

Estes são usados para construir facilmente valores para passar como 'reply_markup':

##### btn
- Constrói um botão para ser usado num InlineKeyboad (um InlineKeyboardButton).
- Parâmetros:
    - text : texto do botão.
    - value : valor para ser usado no botão.
    - type : tipo de botão (callback_data, url, switch_inline_query...)
O _type_ pode ser omitido, se seu valor for callback_data.

##### ikb
- Constrói um InlineKeyboard.
- Parâmetros: uma array, contendo como elementos, outras arrays (linhas), estas contendo as arrays dos botões. Complicado? Não muito:
- Exemplo:
```
$options = [
    [ ['Texto', 'data aqui'] ], # uma linha com um botão com callback_data
    [ ['Outro texto', 't.me/usernein', 'url'] ], # uma linha com um botão com url
    [ ['Texto3', 'botão3'], ['Texto4', 'botão4'] ], # uma linha com dois botões com callback_data
];
$keyboard = ikb($options);
```
- Retorno: um JSON pronto para ser usado em métodos que usam o 'reply_markup'

##### kbtn
- Constrói um ReplyKeyboardButton.
- Parâmetros:
    - text : texto do botão.
    - request_contact : Opcional. Booleano que diz se o botão pedirá o número de telefone. Padrão: false.
    - request_location : Opcional. Booleano que diz se o botão pedirá a localização do usuário. Padrão: false.

##### kb
- Constrói um ReplyKeyboardMarkup.
- Parâmetros:
    - options : uma array de linhas com botões (geradas pelo kbtn), semelhante ao ikb
    - one_time : Opcional. Booleano que diz se o teclado será fechado após o uso. Ele continuará disponível, somente abaixará. Padrão: false.
    - resize : Opcional. Booleano que diz se o teclado pode ser redimensionável. Padrão: false.
    - selective : Opcional. Booleano que diz se o teclado aparecerá para usuários específicos. Padrão: true.
- Exemplo:
```
$options = [
    [ kbtn('Olá!') ],
    [ kbtn('Oi.') ],
];
$keyboard = kb($options);
```
- Retorno: um JSON pronto para ser usado em métodos que usam o 'reply_markup'

##### hide_kb
- Comando usado para deletar o atual teclado do chat.
- Parâmetros:
    - selective : Opcional. Booleano que diz se o teclado desaparecerá para usuários específicos. Padrão: true.
- Retorno: um JSON pronto para ser usado em métodos que usam o 'reply_markup'

##### forceReply
- Comando usado para fazer com que, quando a mensagem for recebida, o cliente do usuário automaticamente abra o teclado como se pressionara 'Responder' à mensagem.
- Parâmetros:
    - selective : Opcional. Booleano que diz se o comando terá efeito para usuários específicos. Padrão: true.
- Retorno: um JSON pronto para ser usado em métodos que usam o 'reply_markup'




