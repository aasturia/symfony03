<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Psr\Log\LoggerInterface;

/**
 * @Route("/notification", name="notification")
 */
class NotificationController extends AbstractController
{
    /**
     * @Route("/send", name="send", methods={"POST"})
     */
    public function create(Request $request, MailerInterface $mailer, ChatterInterface $chatter, LoggerInterface $logger)
    {
        //
//        $logger->info('I just got the logger');
//        $logger->error('An error occurred');
//
//        $logger->critical('I left the oven on!', [
//            // include extra "context" info in your logs
//            'cause' => 'in_hurry',
//        ]);

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
//        $rulesString = ob_get_clean();

        //return response to client
        return new Response(json_encode($projectsNames));
    }

}
