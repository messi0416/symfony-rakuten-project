<?php

namespace Ne\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    public function callbackAction()
    {
        $data = [
              'get' => $_GET
            , 'post' => $_POST
            , 'cookie' => $_COOKIE
        ];

        return new JsonResponse($data);
    }
}
