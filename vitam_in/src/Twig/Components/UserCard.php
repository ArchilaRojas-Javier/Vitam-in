<?php

namespace App\Twig\Components;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsTwigComponent]
final class UserCard
{
    public function __construct(
        private Security $security,
    ) {
    }

    /**
     * Devuelve la entidad User actual o null (solo si es tu App\Entity\User).
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        // Importante: verificar que sea tu entidad, no solo UserInterface
        return $user instanceof User ? $user : null;
    }

    
    /**
     * Nombre completo: "Juan Pérez", solo "Juan", o el email como último recurso.
     */
    public function getFullName(): ?string
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return null;
        }

        $name = trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));

        return $name !== '' ? $name : $user->getEmail();
    }
    
    /**
     * Iniciales: "JP" (nombre + apellido), "J" (solo nombre), o inicial del email.
     */
    public function getInitials(): string
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return '?';
        }

        $initials = '';
        if ($user->getFirstName()) {
            $initials .= mb_strtoupper(mb_substr($user->getFirstName(), 0, 1));
        }
        if ($user->getLastName()) {
            $initials .= mb_strtoupper(mb_substr($user->getLastName(), 0, 1));
        }
        if ($initials === '' && $user->getEmail()) {
            $initials = mb_strtoupper(mb_substr($user->getEmail(), 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }

    public function getAvatarUrl(): ?string
    {
        return $this->getCurrentUser()?->getAvatarUrl();
    }
}




   
