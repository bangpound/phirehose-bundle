<?php

namespace Bangpound\PhirehoseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BangpoundPhirehoseBundle:Default:index.html.twig', array('name' => $name));
    }
}
