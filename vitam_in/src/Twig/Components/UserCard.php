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

    public function getUserName(): ?string
    {
        $user = $this->security->getUser();
        return $user?->getUserIdentifier();
    }

    public function getAvatarUrlFromGoogle(): ?string
    {
        return $this->getCurrentUser()?->getAvatarUrl();
    }
}