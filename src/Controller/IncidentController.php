<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Incident;
use App\Entity\Account;
use App\Service\JsonRequestService;

class IncidentController extends AbstractController
{
    /**
     * @Route("/add_incident", name="add_incident")
     * @Route("/add_incident/", name="add_incident/")
     * 
     * Params: token, name, location, details
     */
    public function index(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $name = $jsr->getArrayKey('name', $parameters);
        $location = $jsr->getArrayKey('location', $parameters);
        $reporter_token = $jsr->getArrayKey('token', $parameters);
        $details = $jsr->getArrayKey('details', $parameters);
        $date = new \DateTime();
        $status = "reported";

        if (!$reporter_token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        if (!$location) {
            return $this->json([
                'error' => 'No location supplied'
            ]);
        }

        if (!$name) {
            return $this->json([
                'error' => 'No name supplied'
            ]);
        }

        if (!$details) {
            return $this->json([
                'error' => 'No details supplied'
            ]);
        }

        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $reporter = $user_repo->findOneBy([
            "token" => $reporter_token
        ]);

        if (!$reporter) {
            return $this->json([
                'error' => 'Please log in before reporting an incident.'
            ]);
        }

        if (!$reporter->getIsValid()) {
            return $this->json([
                'error' => 'Your account must be validated by an admin before you can report incidents.'
            ]);
        }

        /* Add incident to database */
        $incident = new Incident();
        $incident->setName($name);
        $incident->setLocation($location);
        $incident->setDetails($details);
        $incident->setRecordedDate($date);
        $incident->setReporter($reporter);
        $incident->setStatus($status);

        $em->persist($incident);
        $em->flush();

        return $this->json([
            'message' => 'Incident reported successfully!'
        ]);
    }


    /**
     * @Route("/get_incidents", name="get_incidents")
     * @Route("/get_incidents/", name="get_incidents/")
     * 
     * Parameters: none (GET Request)
     */
    public function get_incidents(EntityManagerInterface $em)
    {
        $incident_repo = $this->getDoctrine()->getRepository(Incident::class);
        $all_incidents = $incident_repo->findAll();

        $response = [];

        foreach ($all_incidents as $incident) {
            $reporter = $incident->getReporter();

            $incident_details = [
                "id" => $incident->getId(),
                "name" => $incident->getName(),
                "location" => $incident->getLocation(),
                "recorded_date" => $incident->getRecordedDate(),
                "status" => $incident->getStatus(),
                "details" => $incident->getDetails(),
                "reporter" => $reporter->getUsername()
            ];

            array_push($response, $incident_details);
        }

        return $this->json($response);
    }


    /**
     * @Route("/get_incident", name="get_incident")
     * @Route("/get_incident/", name="get_incident/")
     * 
     * Parameters: id (incident id)
     */
    public function get_incident(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $id = $jsr->getArrayKey('id', $parameters);

        if (!$id) {
            return $this->json([
                'error' => 'No incident id supplied.'
            ]);
        }

        $incident_repo = $this->getDoctrine()->getRepository(Incident::class);
        $incident = $incident_repo->find($id);

        if (!$incident) {
            return $this->json([
                'error' => 'Incident not found.'
            ]);
        }

        $reporter = $incident->getReporter();
        $incident_details = [
            "id" => $incident->getId(),
            "name" => $incident->getName(),
            "location" => $incident->getLocation(),
            "recorded_date" => $incident->getRecordedDate(),
            "status" => $incident->getStatus(),
            "details" => $incident->getDetails(),
            "reporter" => $reporter->getUsername()
        ];

        return $this->json($incident_details);
    }


    /**
     * @Route("/mark_incident", name="mark_incident")
     * @Route("/mark_incident/", name="mark_incident/")
     * 
     * Parameters: token (must be logged as admin), id (incident id), status (reported / verified / solved)
     */
    public function mark_incident(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $id = $jsr->getArrayKey('id', $parameters);
        $status = $jsr->getArrayKey('status', $parameters);

        if (!$id) {
            return $this->json([
                'error' => 'No incident id supplied.'
            ]);
        }

        if (!$token) {
            return $this->json([
                'error' => 'Must be logged as admin in order to change incident status.'
            ]);
        }

        if (!$status) {
            return $this->json([
                'error' => 'No status supplied.'
            ]);
        }

        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            "token" => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Must be logged as admin in order to change incident status.'
            ]);
        }

        $incident_repo = $this->getDoctrine()->getRepository(Incident::class);
        $incident = $incident_repo->find($id);

        if (!$incident) {
            return $this->json([
                'error' => 'Incident not found.'
            ]);
        }

        $incident->setStatus($status);
        $em->persist($incident);
        $em->flush();

        return $this->json([
            'message' => 'Incident status has been successfully changed!'
        ]);
    }


    /**
     * @Route("/delete_incident", name="delete_incident")
     * @Route("/delete_incident/", name="delete_incident/")
     * 
     * Parameters: token (must be logged as admin), id (incident id)
     */
    public function delete_incident(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $id = $jsr->getArrayKey('id', $parameters);

        if (!$id) {
            return $this->json([
                'error' => 'No incident id supplied.'
            ]);
        }

        if (!$token) {
            return $this->json([
                'error' => 'Must be logged as admin in order to delete an incident.'
            ]);
        }

        $user_repo = $this->getDoctrine()->getRepository(Account::class);
        $user = $user_repo->findOneBy([
            "token" => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Must be logged as admin in order to delete an incident.'
            ]);
        }

        $incident_repo = $this->getDoctrine()->getRepository(Incident::class);
        $incident = $incident_repo->find($id);

        if (!$incident) {
            return $this->json([
                'error' => 'Incident not found.'
            ]);
        }

        $em->remove($incident);
        $em->flush();

        return $this->json([
            'message' => 'Incident has been successfully deleted!'
        ]);
    }
}
