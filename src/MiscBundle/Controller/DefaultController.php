<?php

namespace MiscBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('MiscBundle:Default:index.html.twig', array('name' => $name));
    }
}
