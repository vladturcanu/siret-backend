<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\TokenGenerator;
use App\Entity\Sensor;
use App\Service\JsonRequestService;

class SensorController extends AbstractController
{
    /**
     * @Route("/add_sensor", name="add_sensor")
     * @Route("/add_sensor/", name="add_sensor/")
     */
    public function add_sensor(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $location = $jsr->getArrayKey('location', $parameters);
        $parameter = $jsr->getArrayKey('parameter', $parameters);

        if (!$location) {
            return $this->json([
                'error' => 'No location supplied'
            ]);
        }

        if (!$parameter) {
            return $this->json([
                'error' => 'No measured parameter supplied'
            ]);
        }

        $token = TokenGenerator::generateRandomString();

        /* Add sensor to database */
        $sensor = new Sensor();
        $sensor->setLocation($location);
        $sensor->setParameter($parameter);
        $sensor->setToken($token);

        $em->persist($sensor);
        $em->flush();

        return $this->json([
            'message' => 'Sensor added successfully!'
        ]);
    }
}
