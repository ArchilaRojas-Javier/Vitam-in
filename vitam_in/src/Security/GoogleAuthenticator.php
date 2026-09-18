<?php

namespace App\Security;

use App\Entity\User; 
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use App\Enum\Sex;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');

        $accessToken = $this->fetchAccessToken($client);
        
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function ($userIdentifier) use ($client, $accessToken) 
            {
                return $this->loadUserFromGoogle($client, $accessToken);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        
        $targetUrl = $this->router->generate('app_dashboard');

        return new RedirectResponse($targetUrl);
    
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
      
        return new RedirectResponse($this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
            );

    }
    
   /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    private function loadUserFromGoogle(GoogleClient $client, $accessToken): User
    {
        
        // Get the Google user via the API
        /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);

        if (!$googleUser) {
            throw new AuthenticationException("Impossible d'obtenir des informations auprès de Google..");
        }

        // Verify if user exist with Google ID
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['googleId' => $googleUser->getId()]);

        if ($existingUser) {
            return $existingUser;
        }

        // If not exists, search by email.
        $email = $googleUser->getEmail();
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($user) {
            // Link the Google account to the existing user
            $user->setGoogleId($googleUser->getId());
        } else {
            // No existing user found: create a new one
            $user = new User();
            $user->setEmail($email);
            $user->setGoogleId($googleUser->getId());
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $user->setPassword(password_hash(bin2hex(random_bytes(20)), PASSWORD_DEFAULT));
            // La librería expone los datos del perfil a través de toArray()
            $userData = $googleUser->toArray();

            $firstName = $userData['given_name'] ?? null;
            $lastName = $userData['family_name'] ?? null;
            $avatarUrl = $userData['picture'] ?? null;

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setAvatarUrl($avatarUrl);
            $user->setSex(Sex::Other);            
            
        }
        // Get the access token and refresh token
        $tokenData = [
        'access_token' => $accessToken->getToken(),
        'expires_in' => $accessToken->getExpires(),
        'refresh_token' => $accessToken->getRefreshToken(), 
        ];
        // Store the access token and refresh token in the User entity
        $user->setGoogleAccessToken($tokenData);

        if ($accessToken->getRefreshToken()) {
            $user->setGoogleRefreshToken($accessToken->getRefreshToken());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}