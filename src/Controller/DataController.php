<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\TokenGenerator;
use App\Entity\Sensor;
use App\Entity\Data;
use App\Service\JsonRequestService;

class DataController extends AbstractController
{
    /**
     * @Route("/add_data", name="add_data")
     * @Route("/add_data/", name="add_data/")
     * 
     * Parameters: value, token (sensor token)
     */
    public function add_data(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $value = $jsr->getArrayKey('value', $parameters);
        $token = $jsr->getArrayKey('token', $parameters);
        $timestamp = new \DateTime();

        if (!$value) {
            return $this->json([
                'error' => 'No value supplied'
            ]);
        }

        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        $sensor_repo = $this->getDoctrine()->getRepository(Sensor::class);
        $sensor = $sensor_repo->findOneBy([
            "token" => $token
        ]);

        if (!$sensor) {
            return $this->json([
                'error' => 'Sensor not found in database'
            ]);
        }

        /* Add data to database */
        $data = new Data();
        $data->setValue($value);
        $data->setTimestamp($timestamp);
        $data->setSensor($sensor);

        $em->persist($data);
        $em->flush();

        return $this->json([
            'message' => 'Data added successfully!'
        ]);
    }


    /**
     * @Route("/get_data", name="get_data")
     * @Route("/get_data/", name="get_data/")
     * 
     * Parameters: none (GET Request)
     */
    public function get_data(EntityManagerInterface $em)
    {
        $data_repo = $this->getDoctrine()->getRepository(Data::class);
        $all_data = $data_repo->findAll();

        $response = [];

        foreach ($all_data as $data) {
            $sensor = $data->getSensor();

            $data_details = [
                "value" => $data->getValue(),
                "timestamp" => $data->getTimestamp(),
                "parameter" => $sensor->getParameter(),
                "location" => $sensor->getLocation()
            ];

            array_push($response, $data_details);
        }

        return $this->json($response);
    }


    /**
     * @Route("/get_sensor_data", name="get_sensor_data")
     * @Route("/get_sensor_data/", name="get_sensor_data/")
     * 
     * Parameters:
     *      - parameter (measured parameter of the sensor),
     *      - location (location of the sensor)
     */
    public function get_sensor_data(EntityManagerInterface $em)
    {
        /* Get parameters from request */
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $parameter = $jsr->getArrayKey('parameter', $parameters);
        $location = $jsr->getArrayKey('location', $parameters);

        if (!$parameter) {
            return $this->json([
                'error' => 'No parameter supplied'
            ]);
        }

        if (!$location) {
            return $this->json([
                'error' => 'No location supplied'
            ]);
        }

        /* Find requested data from database */
        $data_repo = $this->getDoctrine()->getRepository(Data::class);
        $sensor_repo = $this->getDoctrine()->getRepository(Sensor::class);

        $sensor = $sensor_repo->findOneBy([
            "parameter" => $parameter,
            "location" => $location
        ]);

        if (!$sensor) {
            return $this->json([
                'error' => 'No sensor found for specified parameter and location'
            ]);
        }

        $all_data = $sensor->getData();

        /* Build JSON response */
        $response = [];
        foreach ($all_data as $data) {
            $sensor = $data->getSensor();

            $data_details = [
                "value" => $data->getValue(),
                "timestamp" => $data->getTimestamp()
            ];

            array_push($response, $data_details);
        }

        return $this->json($response);
    }
}
