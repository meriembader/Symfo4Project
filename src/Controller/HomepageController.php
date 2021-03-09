<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends Controller {

    /**
     * @Route("/")
     */
    public function index() {
        return $this->render('base.html.twig', ['mainNavHome'=>true, 'title'=>'Accueil']);
    }

}