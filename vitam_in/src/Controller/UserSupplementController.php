<?php

namespace App\Controller;

use App\Entity\UserSupplement;
use App\Entity\User;
use App\Form\UserSupplementType;
use App\Service\UserSupplementService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReminderRepository;
use App\Repository\UserSupplementRepository;   
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\GoogleCalendarService;



#[Route('/user/supplement')]
#[IsGranted('ROLE_USER')]
final class UserSupplementController extends AbstractController
{
    #[Route('/new/{supplementId}', name: 'app_user_supplement_new', methods: ['GET', 'POST'])]
public function new(Request $request, UserSupplementService $userSupplementService, GoogleCalendarService $googleCalendarService, int $supplementId): Response 
{
    /** @var User $user */
    $user = $this->getUser();
    
    $existing = $userSupplementService->findExistingUserSupplement($user, $supplementId);

    if ($existing) {
        $this->addFlash('warning', 'Vous avez déjà ajouté ce supplément à votre liste.');
        return $this->redirectToRoute('app_dashboard');
    }
    
    try {
        $userSupplement = $userSupplementService->createUserSupplementFromSupplementId($supplementId);
    } catch (\InvalidArgumentException $e) {
        $this->addFlash('error', $e->getMessage());
        return $this->redirectToRoute('app_dashboard');
    }

    $form = $this->createForm(UserSupplementType::class, $userSupplement);
    
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Check if the user wants Google Calendar and has a token BEFORE saving.
        $createGoogleEvent = $form->get('google_calendar')->getData();
    
        if ($createGoogleEvent) {
            $accessToken = $user->getGoogleAccessToken();
            if (!$accessToken) {
                $this->addFlash('warning', 'Vous devez être connecté à Google pour utiliser cette option..');
                return $this->redirectToRoute('app_dashboard');
            }
        }

        $userSupplementService->persistWithUser($userSupplement, $user);
        $this->addFlash('success', 'Supplément ajouté avec succès.');

        // If the user wants an event and has a token, create it.
        if ($createGoogleEvent) {
            try {
                $eventId = $googleCalendarService->createUserSupplementEvent($user, $userSupplement);
                if ($eventId) {
                
                    $this->addFlash('success', 'Événement créé dans Google Agenda.');
                }
            } catch (\Google\Service\Exception $e) {
                $this->addFlash('warning', 'Erreur avec Google' );
            } catch (\RuntimeException $e) {
                
                $this->addFlash('warning', "L'événement n'a pas pu être créé." );
            } catch (\Exception $e) {
                
                $this->addFlash('error', "Une erreur inattendue s'est produite");
                
            }
        }
        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);

    }

    return $this->render('user_supplement/new.html.twig', [
        'user_supplement' => $userSupplement,
        'form' => $form,
    ]);
}

   #[Route('/{id}/edit', name: 'app_user_supplement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserSupplement $userSupplement, GoogleCalendarService $googleCalendarService,
        ReminderRepository $reminderRepository, EntityManagerInterface $entityManager): Response 
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($userSupplement->getUser() !== $user) {
            throw new AccessDeniedException('Vous n\'avez pas le droit de modifier ce supplément.');
        }

        $activeReminder = $reminderRepository->findActiveByUserSupplement($userSupplement);

        $form = $this->createForm(UserSupplementType::class, $userSupplement);
        $form->get('google_calendar')->setData($activeReminder !== null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->flush();

            $hasGoogleToken = (bool) $user->getGoogleAccessToken();
            $createGoogleEvent = $form->get('google_calendar')->getData();

            if (!$hasGoogleToken) {
                
                if ($createGoogleEvent) {
                    $this->addFlash('warning', 'Vous devez lier votre compte Google pour utiliser le calendrier.');
                }
                $this->addFlash('success', 'Supplément modifié avec succès.');
                return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
            }

            try {
                if ($createGoogleEvent) {
                    
                    if ($activeReminder && $activeReminder->getGoogleEventId()) {
                        
                        $googleCalendarService->updateEvent($userSupplement, $activeReminder->getGoogleEventId());
                        $this->addFlash('success', 'Événement Google Calendar mis à jour.');
                    } else {
                        
                        $eventId = $googleCalendarService->createUserSupplementEvent($user, $userSupplement);
                        if ($eventId) {
                            $this->addFlash('success', 'Événement créé dans Google Agenda.');
                        }
                    }
                } else {
                    
                    if ($activeReminder && $activeReminder->getGoogleEventId()) {
                        $googleCalendarService->deleteEvent($user, $activeReminder->getGoogleEventId());
                        
                        $entityManager->remove($activeReminder);
                        $entityManager->flush();
                        $this->addFlash('success', 'Événement supprimé de Google Agenda.');
                    }
                    
                }
            } catch (\Google\Service\Exception $e) {
                $this->addFlash('error', 'Erreur avec Google Calendar ');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Erreur lors de l\'opération sur le calendrier ');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur inattendue est survenue.');
                
            }

            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        
        return $this->render('user_supplement/edit.html.twig', [
            'user_supplement' => $userSupplement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_supplement_delete', methods: ['POST'])]
    public function delete(Request $request, UserSupplement $userSupplement, GoogleCalendarService $googleCalendarService, ReminderRepository $reminderRepository, EntityManagerInterface $entityManager): Response
    {
    
        if ($userSupplement->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous n\'avez pas le droit de supprimer ce supplément.');
        }

        $token = $request->getPayload()->getString('_token');
        if (!$this->isCsrfTokenValid('delete' . $userSupplement->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        $activeReminder = $reminderRepository->findActiveByUserSupplement($userSupplement);
        if ($activeReminder && $activeReminder->getGoogleEventId()) {
            try {
                
                $googleCalendarService->deleteEvent($userSupplement->getUser(),$activeReminder->getGoogleEventId());
            
            } catch (\RuntimeException $e) {
                
                $this->addFlash('error', 'L\'événement n\'a pas pu être supprimé du calendrier. Veuillez réessayer.');
                return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
            }
        }
       

        $entityManager->remove($userSupplement);
        $entityManager->flush();

        $this->addFlash('success', 'Supplément supprimé avec succès.');
        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/historique', name: 'app_user_supplement_history', methods: ['GET'])]
    public function history(Request $request, UserSupplementRepository $userSupplementRepository,): Response 
    {
        /** @var User $user */
        $user = $this->getUser();

        $dataUserSupplement = $userSupplementRepository->findForHistory($user);   
        
        return $this->render('user_supplement/history.html.twig', [
           'dataUserSupplement' => $dataUserSupplement,
        ]);
    }

}
