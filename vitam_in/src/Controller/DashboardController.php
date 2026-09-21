<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\SupplementRepository;    

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/dashboard/admin', name: 'app_dashboard_admin')]
    public function admin(SupplementRepository $supplementRepository): Response
    {   
        // security lanza un accesdeniedepxepcion cuando se intenta acceder a esta ruta sin el rol 
        // $user = $this->getUser()->getRoles();
        // if (!in_array('ROLE_ADMIN', $user)) {
        //     $this->addFlash('error', 'Accès refusé. Seuls les administrateurs peuvent accéder à cette page.');
        //     return $this->redirectToRoute('app_login');
        // }
        $supplements = $supplementRepository->findAll();

        return $this->render('dashboard/admin.html.twig', [
            'supplements' => $supplements,
        ]);
    }  
}
