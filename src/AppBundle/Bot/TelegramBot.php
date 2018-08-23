<?php

namespace AppBundle\Bot;

use React\EventLoop\Factory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramAPI\Telegram\Methods\SendSticker;
use unreal4u\TelegramAPI\Telegram\Types\CallbackQuery;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;


class TelegramBot
{
    const PHOTO_PATH = "/var/www/projects/tg_bot/app/Resources/img/photo.jpeg";
    const STICKER = "CAADAgADWwADCcWmA3XV3yBO64V3Ag";
    const KEYBOARD_BUTTON_1 = "Жмяк!";
    const KEYBOARD_BUTTON_2 = "Ещё жмяк!";
    const KEYBOARD_BUTTON_3 = "НЕ НАЖИМАТЬ!";
    const CALLBACK_DATA_PHOTO = "Картинка";
    const CALLBACK_DATA_STICKER = "Стикер";
    const START_TEXT = "Добро пожaловать, %s";
    const HELP_TEXT = "Увы, ничем помочь не могу";
    const BUTTONS_TEXT = "Жмякай наздоровье";
    const INLINE_BUTTONS_TEXT = "Нажми и посмотри, что будет";

    private $message;
    private $chatId;
    private $botCommand;
    private $response;
    private $token;
    private $from;
    private $queryData;
    private $keyword;

    /**
     * TelegramBot constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->token = $container->getParameter("bot_token");
    }

    /**
     * @param Update $update
     */
    public function run(Update $update):void
    {
        $this->extractUpdateObject($update);
        $this->createAnswer();
    }

    private function createAnswer():void
    {
        if (!empty($this->keyword)) {
            $this->switchKeyword();
        } elseif (!empty($this->botCommand)) {
            $this->switchBotCommand();
        } elseif (!empty($this->queryData)) {
            $this->switchQueryData();
        }
    }

    private function switchKeyword():void
    {
        $this->createMessageResponse();

        switch ($this->keyword) {
            case self::KEYBOARD_BUTTON_3:
                $this->response->text = "А ты любишь хулиганить";
                break;
            case self::KEYBOARD_BUTTON_1:
                $this->response->text = "Понравилось?";
                break;
            case self::KEYBOARD_BUTTON_2:
                $this->response->text = "Хватит";
                break;
            case "Привет":
                //no break
            case "привет":
                $this->response->text = "Здарова!";
                break;
            default:
                $this->response->text = "Не понимаю о чём ты";
                break;

        }
    }

    private function switchBotCommand():void
    {
        $this->createMessageResponse();

        switch ($this->botCommand) {
            case "start":
                $this->start();
                break;
            case "help":
                $this->help();
                break;
            case "buttons":
                $this->buttons();
                break;
            case "inlinebuttons":
                $this->inlineButtons();
                break;
            default:
                $this->response->text = "Я не знаю таких команд";
                break;
        }
    }

    private function switchQueryData():void
    {
        switch ($this->queryData) {
            case self::CALLBACK_DATA_PHOTO:
                $this->createPhotoResponse();
                $this->response->photo = new InputFile(self::PHOTO_PATH);
                $this->response->caption = "Любуйся";
                break;
            case self::CALLBACK_DATA_STICKER:
                $this->createStickerResponse();
                $this->response->sticker = self::STICKER;
                break;
            default:
                $this->createMessageResponse();
                $this->response->text = "Что-то пошло не так!";
                break;
        }
    }

    /**
     * @param Update $update
     */
    private function extractUpdateObject(Update $update):void
    {
        foreach ($update as $field => $value) {
            if ($value instanceof Message) {
                $this->extractMessage($value);
                break;
            } elseif ($value instanceof CallbackQuery) {
                $this->extractCallbackQuery($value);
                break;
            }
        }
    }

    /**
     * @param CallbackQuery $query
     */
    private function extractCallbackQuery(CallbackQuery $query):void
    {
        $this->from = $query->from;
        $this->chatId = $query->message->chat->id;
        $this->queryData = $query->data;
    }

    /**
     * @param Message $message
     */
    private function extractMessage(Message $message):void
    {
        $this->message = $message;
        $this->chatId = $message->chat->id;
        $this->from = $message->from;

        if (!empty($this->message->entities)) {
            $this->handleEntities();
        } elseif (!empty($this->message->text)) {
            $this->handleMessageText();
        }
    }

    private function handleMessageText():void
    {
        $this->keyword = $this->message->text;
    }

    private function handleEntities():void
    {
        foreach ($this->message->entities as $entity) {
            switch ($entity->type) {
                case "bot_command":
                    $this->handleBotCommand($entity);
                    break;
                // case "email":
                // case "mention":
                // etc.
            }
        }
    }

    /**
     * @param MessageEntity $entity
     */
    private function handleBotCommand(MessageEntity $entity):void
    {
        $this->botCommand = trim(substr($this->message->text, $entity->offset + 1, $entity->length));

        if (strpos($this->botCommand, "@") !== false) {
            $this->botCommand = substr($this->botCommand, 0, strpos($this->botCommand, "@"));
        }
    }

    private function createMessageResponse():void
    {
        $this->response = new SendMessage();
        $this->response->chat_id = $this->chatId;
    }

    private function createPhotoResponse():void
    {
        $this->response = new SendPhoto();
        $this->response->chat_id = $this->chatId;
    }

    private function createStickerResponse():void
    {
        $this->response = new SendSticker();
        $this->response->chat_id = $this->chatId;
    }

    public function sendResponse():void
    {
        if ($this->response !== null) {
            $loop = Factory::create();
            $tgLog = new TgLog($this->token, new HttpClientRequestHandler($loop));
            $tgLog->performApiRequest($this->response);
            $loop->run();
        }
    }

    private function start():void
    {
        $this->response->text = sprintf(self::START_TEXT,  $this->from->first_name);
    }

    private function buttons():void
    {
        $this->response->text = self::BUTTONS_TEXT;
        $this->response->reply_markup = new ReplyKeyboardMarkup();
        $this->response->reply_markup->one_time_keyboard = true;

        $button = new KeyboardButton();
        $button->text = self::KEYBOARD_BUTTON_1;
        $this->response->reply_markup->keyboard[0][] = $button;

        $button = new KeyboardButton();
        $button->text = self::KEYBOARD_BUTTON_2;
        $this->response->reply_markup->keyboard[0][] = $button;

        $button = new KeyboardButton();
        $button->text = self::KEYBOARD_BUTTON_3;
        $this->response->reply_markup->keyboard[1][] = $button;
    }

    private function inlineButtons():void
    {
        $this->response->text = self::INLINE_BUTTONS_TEXT;
        $inlineKeyboard = new Markup(
            [
                "inline_keyboard" => [
                    [
                        ['text' => "Картинка", "callback_data" => self::CALLBACK_DATA_PHOTO,],
                        ['text' => "Стикер", "callback_data" => self::CALLBACK_DATA_STICKER,],
                    ],
                ],
            ]
        );
        $this->response->reply_markup = $inlineKeyboard;
    }

    private function help():void
    {
        $this->response->text = self::HELP_TEXT;
    }
}