<?php


namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Twig\Loader\FilesystemLoader;


class Transport
{
    private $effects;
    private $mailer;
    private $chatter;

    public function __construct(Rules $rules, $mailer, $chatter)
    {
        $this->effects = $rules->effects;
        $this->mailer = $mailer;
        $this->chatter = $chatter;
    }

    //send notifications through transport according "type" field in rules effects
    public function sendNotification($data, $logger)
    {
        foreach ($this->effects as $effect) {
            $transportName = ucwords($effect->type);
            $sendFunction = "sendThrough" . $transportName;
            $this->$sendFunction($data, $effect, $logger);
        }
    }

    public function sendThroughSmtp($data, $effect)
    {
        $email = (new TemplatedEmail())
            ->from('alex.canzona@gmail.com')
            ->to(new Address($effect->recipient))
            ->subject('Тестовое письмо из Symfony')
            ->htmlTemplate('emails/email' . ($effect->template_id) . '.html.twig')
            ->context([
                'projects' => $data,
                'username' => 'Asturia',
            ]);

        $this->mailer->send($email);
    }

    private function sendThroughTelegram($projects, $effect, $logger)
    {
//        $data = getPlacefolders($projects, $effect);

        $result = [];

        foreach ($projects as $project) {
            $logger->info(json_encode($project));
            foreach ($effect->placeholders as $key => $value) {
                array_push($result, ['placeholderName'=>$key, 'placeholderField'=> $value, 'projectField' => $project->$key ]);
            }
        }

        $logger->info('привет'. json_encode( $result));

        $loader = new FilesystemLoader('../templates/telegram/');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load('telegram' . ($effect->template_id) . '.html.twig');
        $message = $template->render(['projects' => $result]);

        $chatMessage = new ChatMessage($message);
        $telegramOptions = (new TelegramOptions())
            ->chatId($effect->recipient)
            ->parseMode('html');
        $chatMessage->options($telegramOptions);

        $this->chatter->send($chatMessage);
    }

}