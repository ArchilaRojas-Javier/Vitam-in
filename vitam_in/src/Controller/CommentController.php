<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\SupplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route('/new/comment/{supplementId}', name: 'app_comment_new')]
    public function new(Request $request, int $supplementId, SupplementRepository $supplementRepository,
                        EntityManagerInterface $entityManagerInterface): Response 
    {
        $supplement = $supplementRepository->find($supplementId);
        if (!$supplement) {
            throw $this->createNotFoundException('Supplément introuvable');
        }
        
        $comment = new Comment();
        $comment->setSupplement($supplement);
        $comment->setUser($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setIsApprouved(true); // por el momento 
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Verify that the user is logged in.
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            $entityManagerInterface->persist($comment);
            $entityManagerInterface->flush();
            
            return $this->render('supplement/show.html.twig', [
                'supplement' => $supplement,
            ]);
        }

        if ($request->headers->get('turbo-frame') === 'supplement-detail') {
            return $this->render('comment/new_frame.html.twig', [
                'form' => $form->createView(),
                'supplement' => $supplement,
            ]);
        }

        // en cas d'accès direct via l'URL
        return $this->render('comment/new.html.twig', [
            'form' => $form->createView(),
            'supplement' => $supplement,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $supplement = $comment->getSupplement();
         if ($comment->getUser() !== $this->getUser()) {
            return $this->render('supplement/show.html.twig', [
            'supplement' => $supplement,
            ]);
        }
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->render('supplement/show.html.twig', [
            'supplement' => $supplement,
        ]);
    }

    #[Route('/list', name: 'app_comment_list')]
    public function list(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findAll();

        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }
}
