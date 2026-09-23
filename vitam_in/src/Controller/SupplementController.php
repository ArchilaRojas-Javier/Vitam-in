<?php
namespace App\Controller;

use App\Repository\SupplementRepository;
use App\Entity\Supplement;  
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\SupplementType;

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
    public function edit(int $id, SupplementRepository $supplementRepository, Request $request, EntityManagerInterface $em): Response
    {
        $supplement = $supplementRepository->find($id);

        if (!$supplement) {
            throw $this->createNotFoundException('Supplément introuvable.');
        }

        $defaultProfile = [
            'dose'    => null,
            'unit'    => 'mg',
            'moments' => ['morning' => 0, 'noon' => 0, 'evening' => 0],
        ];

        $form = $this->createForm(SupplementType::class, $supplement);

        // Rellenar los 3 sub-formularios NO mapeados antes de handleRequest
        foreach (['male', 'female', 'general'] as $key) {
            $form->get($key)->setData($defaultProfile);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
        $supplement->setDosageSchedule([
                'male'    => $form->get('male')->getData(),
                'female'  => $form->get('female')->getData(),
                'general' => $form->get('general')->getData(),
            ]);
        $em->persist($supplement);
        $em->flush();

        $this->addFlash('success', 'Le complément a été ajouté.');
        return $this->redirectToRoute('app_supplement_index', [], Response::HTTP_SEE_OTHER);
    }
        return $this->render('supplement/edit.html.twig', [
            'supplement' => $supplement,
            'form' => $form,
        ]);
    }

     #[Route('/supplement/{id}/delete', name: 'app_supplement_delete', methods: ['POST'])]
    public function delete(Request $request, Supplement $supplement, EntityManagerInterface $entityManager): Response
    {
       
        if ($this->isCsrfTokenValid('delete'.$supplement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($supplement);
            $entityManager->flush();
        }

        return $this->redirect('supplement/list.html.twig', Response::HTTP_SEE_OTHER);
    }

    #[Route('/supplement/new', name: 'app_supplement_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $supplement = new Supplement();
        $defaultProfile = [
            'dose'    => null,
            'unit'    => 'mg',
            'moments' => ['morning' => 0, 'noon' => 0, 'evening' => 0],
        ];

        $form = $this->createForm(SupplementType::class, $supplement);

        // Rellenar los 3 sub-formularios NO mapeados antes de handleRequest
        foreach (['male', 'female', 'general'] as $key) {
            $form->get($key)->setData($defaultProfile);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        
            $supplement->setDosageSchedule([
                'male'    => $form->get('male')->getData(),
                'female'  => $form->get('female')->getData(),
                'general' => $form->get('general')->getData(),
            ]);
            $em->persist($supplement);
            $em->flush();

            $this->addFlash('success', 'Le complément a été ajouté avec succès.');

            return $this->redirectToRoute('app_supplement_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('supplement/new.html.twig', [
            'supplement' => $supplement,
            'form' => $form,
        ]);
    }
}