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

    /**
     * @Route("/edit_sensor_coords", name="edit_sensor_coords")
     * @Route("/edit_sensor_coords/", name="edit_sensor_coords/")
     */
    public function edit_sensor_coords(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $sensor_id = $jsr->getArrayKey('sensor_id', $parameters);
        $longitude = $jsr->getArrayKey('longitude', $parameters);
        $latitude = $jsr->getArrayKey('latitude', $parameters);

        if (!$sensor_id) {
            return $this->json([
                'error' => 'No sensor_id supplied'
            ]);
        }

        if (!$longitude) {
            return $this->json([
                'error' => 'No longitude supplied'
            ]);
        }

        if (!$latitude) {
            return $this->json([
                'error' => 'No latitude supplied'
            ]);
        }

        $sensor_repo = $this->getDoctrine()->getRepository(Sensor::class);

        $sensor = $sensor_repo->find($sensor_id);
        if (!$sensor) {
            return $this->json([
                'error' => 'Sensor not found in database'
            ]);
        }

        $sensor->setLongitude($longitude);
        $sensor->setLatitude($latitude);

        $em->persist($sensor);
        $em->flush();

        return $this->json([
            'message' => 'Sensor coordinates edited successfully!'
        ]);
    }

    /**
     * @Route("/get_sensors", name="get_sensors")
     * @Route("/get_sensors/", name="get_sensors/")
     */
    public function get_sensors(EntityManagerInterface $em)
    {
        $sensor_repo = $this->getDoctrine()->getRepository(Sensor::class);
        $all_sensors = $sensor_repo->findAll();

        $response = [];
        foreach ($all_sensors as $sensor) {

            $response_row = [
                "id" => $sensor->getId(),
                "location" => $sensor->getLocation(),
                "param_type" => $sensor->getParameter(),
                "longitude" => $sensor->getLongitude(),
                "latitude" => $sensor->getLatitude()
            ];

            array_push($response, $response_row);
        }

        return $this->json($response);
    }
}
