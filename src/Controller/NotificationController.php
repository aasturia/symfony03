<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\ChatterInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


/**
 * @Route("/notification", name="notification")
 */
class NotificationController extends AbstractController
{
    /**
     * @Route("/send", name="send", methods={"POST"})
     */
    public function create(Request $request, MailerInterface $mailer, ChatterInterface $chatter)
    {

        // create a log channel
        $logger = new Logger('notification-service');
        $logger->pushHandler(new StreamHandler('../var/log/notifications.log', Logger::DEBUG));

        // add records to the log
//        $logger->warning('Foo1');
//        $logger->error('Bar2');
//        $logger->notice('Adding a new user3');
        $logger->info('request received from IP: '.json_encode($request->getClientIp()));

        //decode request data:
        $data = json_decode($request->getContent(), false);
        $projects = $data->projects;


        //find filtered projects
        $rules = new Rules;
        $filteredProjects = array_filter($projects, function ($project) use ($rules) {
            return $rules->isConditionsTrue($project);
        });

        //find filtered projects names
        $projectsNames = array_reduce($filteredProjects, function ($acc, $project) {
            array_push($acc, $project->name);
            return $acc;
        }, []);

        //send notifications through all transports
        $transport = new Transport($rules, $mailer, $chatter);
        $transport->sendNotification($filteredProjects, $logger);

//        ob_start();
//        var_dump($rules);
//        $rulesData = ob_get_clean();

        //return response to client
        return new Response(json_encode($projectsNames));
    }

}
