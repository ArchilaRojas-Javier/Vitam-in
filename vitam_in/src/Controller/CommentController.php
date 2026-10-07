<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\ResponseRepository;
use App\Repository\SupplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;



#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route('/new/comment/{supplementId}', name: 'app_comment_new', methods: ['GET', 'POST'])]
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
        $comment->setIsApprouved(false); 
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Verify that the user is logged in.
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            $entityManagerInterface->persist($comment);
            $entityManagerInterface->flush();
            // dd($request->headers);
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès et est en attente de validation.');
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

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Comment $comment, CommentRepository $commentRepository): Response
    {
        $comment = $commentRepository->find($comment->getId());
        dump($comment);
        foreach ($comment->getResponse() as $r) {
        dump($r);          // ¿qué clase es? ¿tiene ->user?
        dump($r->getUser()); // ¿es null o es un User?
    }
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
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
    #[IsGranted('ROLE_ADMIN')]
    public function list(Request $request, CommentRepository $commentRepository, ResponseRepository $responseRepository): Response
    {
        $status = $request->query->get('status', 'pending');

        $comments = match ($status) {
            'approved' => $commentRepository->findBy(
                ['is_approuved' => true],
                ['created_at' => 'DESC']
            ),
            'pending' => $commentRepository->findBy(
                ['is_approuved' => false],
                ['created_at' => 'DESC']
            ),
            'reported' => $responseRepository->findBy(
                ['id' => 'DESC'],
                ['created_at' => 'DESC']
            ),
            
        }; 

        $counts = [
            'pending'  => $commentRepository->count(['is_approuved' => false]),
            'reported' => $responseRepository->count(['id' => 'DESC']),
            'approved' => $commentRepository->count(['is_approuved' => true]),
        ];

        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
            'status'   => $status,
            'counts'   => $counts,
        ]);
    }

    #[Route('/admin/comment/{id}/approve', name: 'app_comment_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('approve'.$comment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $comment->setIsApprouved(true);
        $em->flush();
        $this->addFlash('success', 'Commentaire approuvé.');
        return $this->redirectToRoute('app_comment_list');
    }

    //Pour l'instant, un message rejeté sera supprimé, mais nous prévoyons d'ajouter 
    // ultérieurement un champ `isRejected` à l'entité `comment` afin de le sauvegarder.

    #[Route('/admin/comment/{id}/reject', name: 'app_comment_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('reject'.$comment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($comment);
        $em->flush();
        $this->addFlash('success', 'Commentaire supprimé.');
        return $this->redirectToRoute('app_comment_list');
    }
}
