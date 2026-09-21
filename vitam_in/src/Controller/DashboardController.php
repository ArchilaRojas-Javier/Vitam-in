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
        
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_dashboard_admin');
        }
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/dashboard/admin', name: 'app_dashboard_admin')]
    public function admin(SupplementRepository $supplementRepository): Response
    {   
       
        $supplements = $supplementRepository->findAll();

        return $this->render('dashboard/admin.html.twig', [
            'supplements' => $supplements,
        ]);
    }  
}
