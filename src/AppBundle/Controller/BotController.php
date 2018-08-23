<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Bot\TelegramBot;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class BotController extends Controller
{
    private $bot;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request):Response
    {
        $content = json_decode($request->getContent(), true);
        $update = new Update($content);

        $this->bot->run($update);
        var_dump($this->bot->sendResponse());

        return new Response();
    }
}