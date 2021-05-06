<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="paprec_home_home")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function indexAction() : Response
    {
        return $this->render('home/index.html.twig');
    }
}
