<?php
namespace App\Controller;

use App\Repository\SupplementRepository;
use App\Entity\Supplement;  
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SupplementController extends AbstractController
{
    #[Route('/supplement/{id}', name: 'app_supplement_show', requirements: ['id' => '\d+'])]
    public function show(int $id, SupplementRepository $supplementRepository, Request $request): Response
    {
        $supplement = $supplementRepository->find($id);

        if (!$supplement) {
            throw $this->createNotFoundException('Supplément introuvable.');
        }

        return $this->render('supplement/show.html.twig', [
            'supplement' => $supplement,
        ]);
    }

    #[Route('/supplement', name: 'app_supplement_list')]
    public function list(SupplementRepository $supplementRepository): Response
    {
        $supplements = $supplementRepository->findAll();

        return $this->render('supplement/list.html.twig', [
            'supplements' => $supplements,
        ]);
    }

    #[Route('/supplement/{id}/edit', name: 'app_supplement_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, SupplementRepository $supplementRepository): Response
    {
        $supplement = $supplementRepository->find($id);

        if (!$supplement) {
            throw $this->createNotFoundException('Supplément introuvable.');
        }

        return $this->render('supplement/edit.html.twig', [
            'supplement' => $supplement,
        ]);
    }

     #[Route('/supplement/{id}/delete', name: 'app_supplement_delete', methods: ['POST'])]
    public function delete(Request $request, Supplement $supplement, EntityManagerInterface $entityManager): Response
    {
       
        if ($this->isCsrfTokenValid('delete'.$supplement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($supplement);
            $entityManager->flush();
        }

        return $this->render('supplement/show.html.twig', [
            'supplement' => $supplement,
        ]);
    }

    #[Route('/supplement/new', name: 'app_supplement_new')]
    public function new(): Response
    {
        return $this->render('supplement/new.html.twig');
    }
}   