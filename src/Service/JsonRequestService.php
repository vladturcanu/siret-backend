<?php

namespace App\Service;

class JsonRequestService
{
    /**
     * Safely get a key from an array.
     * If the key exists, return the key.
     * Else, return FALSE
     */
    public function getArrayKey($key, $array) {
        return array_key_exists($key, $array) ? $array[$key] : FALSE;
    }

    /**
     * Convert JSON from the request body into an associative array.
     * If the JSON exists and is valid (and not empty, unless $allowEmpty is TRUE), return it as an array.
     * Else, return FALSE
     */
    public function getRequestBody($request, $allowEmpty = FALSE) {
        $parameters = [];
        if ($content = $request->getContent()) {
            $parameters = json_decode($content, true);

            if (!$parameters && !$allowEmpty) {
                return FALSE;
            }

            return $parameters;
        } else if ($allowEmpty) {
            return [];
        } else {
            return FALSE;
        }
    }

    /**
     * Get bearer token from the request headers if it exists.
     * If the token exists and is valid, return the token in a string
     * Else, return FALSE
     */
    public function getBearerToken($request) {
        if ($header = $request->headers->get('authorization')) {
            /* Token looks like this: "Bearer <token>". Extract the token */
            if (strpos($header, 'Bearer ') != 0) {
                return FALSE;
            } else {
                return substr($header, 7);
            }
        } else {
            return FALSE;
        }
    }
}