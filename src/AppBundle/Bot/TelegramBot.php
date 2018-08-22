<?php

namespace AppBundle\Bot;

use React\EventLoop\Factory;
use React\Promise\PromiseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use unreal4u\TelegramAPI\Abstracts\TraversableCustomType;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetUpdates;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;


class TelegramBot
{
    private $message;
    private $chatId;
    private $botCommand;
    private $response;
    private $token;

    public function __construct(ContainerInterface $container)
    {
        $this->token = $container->getParameter("bot_token");
    }

    public function test(Update $update):void
    {
        $this->extractUpdateObject($update);
        $this->createMessageResponse();
        switch ($this->botCommand) {
            case "start":
                $this->response->text = "Добро пожаловать на борт!";
                break;
            case "help":
                $this->response->text = "Помощь";
                break;
            default:
                $this->response->text = "Я не знаю такой команды!";
                break;
        }
    }

//    /**
//     * @param string $message
//     * @param int $chat_id
//     */
//    private function sendMessage(string $message, int $chat_id):void
//    {
//        $sendMessage = new SendMessage();
//        $sendMessage->text = $message;
//        $sendMessage->chat_id = $chat_id;
//
//        $this->tgLog->performApiRequest($sendMessage);
//    }

    /**
     * @param Update $update
     */
    private function extractUpdateObject(Update $update):void
    {
        if ($update->message instanceof Message) {
            $this->message = $update->message;
            $this->chatId = $update->message->chat->id;

            $this->extractBotCommand();
        }
    }

    private function extractBotCommand():void
    {
        foreach ($this->message->entities as $entity) {
            if ($entity->type === "bot_command") {
                $this->botCommand = trim(substr($this->message->text, $entity->offset + 1, $entity->length));

                if (strpos($this->botCommand, "@") !== false) {
                    $this->botCommand = substr($this->botCommand, 0, strpos($this->botCommand, "@"));
                }
            }
        }
    }

    private function createMessageResponse():void
    {
        $this->response = new SendMessage();
        $this->response->chat_id = $this->chatId;
    }

    public function sendResponse()
    {
        if ($this->response !== null) {
            $loop = Factory::create();
            $tgLog = new TgLog($this->token, new HttpClientRequestHandler($loop));
            $tgLog->performApiRequest($this->response);
            $loop->run();
        }
    }
}