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
    private $lastUPD;
    private $objUPD;
    private $tgLog;
    private $loop;

    public function __construct(ContainerInterface $container)
    {
        $token = $container->getParameter("bot_token");
        $this->objUPD = new GetUpdates();
        $this->loop = Factory::create();
        $this->tgLog = new TgLog($token, new HttpClientRequestHandler($this->loop));
    }

    public function test():void
    {
        $this->loop->addPeriodicTimer(3, function () {
            $result = $this->getUpdate();
            $result->then(
                function (TraversableCustomType $updates) {
                    foreach ($updates as $update) {
                        $chat_id = $update->message->chat->id;
                        switch ($update->message->text) {
                            case "/start":
                                $message = "Добро пожаловать!";
                                break;
                            case "/help":
                                $message = "Бог поможет";
                                break;
                            case "Привет":
                                $message = "Здарова";
                                break;
                            case "Пёс":
                            case "пёс":
                                $message = "Обидно";
                                break;
                            default:
                                $message = "Скажи, что я пёс!";
                        }
                        $this->sendMessage($message, $chat_id);
                    }
                }
            );
        });
        $this->loop->run();
    }

    /**
     * @return PromiseInterface
     */
    public function getUpdate():PromiseInterface
    {
        $this->objUPD->offset = $this->lastUPD;
        $promise = $this->tgLog->performApiRequest($this->objUPD);
        $promise->then(
            function (TraversableCustomType $response) {
                $this->lastUPD = ++$response->data[count($response->data)-1]->update_id;
            },
            function (\Exception $e) {
                echo "Ошибка: ".get_class($e)."\n";
                echo "Текст ошибки:".$e->getMessage();
                echo "\n";
            }
        );

        return $promise;
    }

    /**
     * @param string $message
     * @param int $chat_id
     */
    private function sendMessage(string $message, int $chat_id):void
    {
        $sendMessage = new SendMessage();
        $sendMessage->text = $message;
        $sendMessage->chat_id = $chat_id;

        $this->tgLog->performApiRequest($sendMessage);
    }
}