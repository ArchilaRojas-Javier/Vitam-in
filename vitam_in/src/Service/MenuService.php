<?php
namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;

class MenuService
{
    public function __construct(
        private Security $security,
    ) {
    }
    /**
     * Devuelve los items del menú comunes para header y navbar móvil.
     * Cada item incluye: label, route, icon (nombre de un icono SVG o clase).
     */
    public function getMenuItems(): array
    {
        if ($this->security->getUser()) {
            // Utilisateur connecté : seuls ces liens sont affichés.
            if ($this->security->isGranted('ROLE_ADMIN')) {
                // Utilisateur connecté avec roll admin
                return [
                    // ['label' => 'Dashboard',  'route' => 'app_dashboard', 'icon' => 'lucide:home'],
                    ['label' => 'Mon Profile',  'route' => 'app_user_profile', 'icon' => 'lucide:user'],
                    // ['label' => 'Historique',  'route' => 'app_user_supplement_history', 'icon' => 'lucide:notebook'],
                    ['label' => 'Admin',  'route' => 'app_dashboard_admin', 'icon' => 'lucide:shield'],
                    ['label' => 'Cerrar sesión', 'route' => 'app_logout', 'icon' => 'lucide:log-out'],
                ];
            }
                // Utilisateur connecté avec roll user
            return [
                ['label' => 'Dashboard',  'route' => 'app_dashboard', 'icon' => 'lucide:home'],
                ['label' => 'Mon Profile',  'route' => 'app_user_profile', 'icon' => 'lucide:user'],
                // ['label' => 'Notes',  'route' => 'app_notes', 'icon' => 'lucide:notepad'],
                ['label' => 'Historique',  'route' => 'app_user_supplement_history', 'icon' => 'lucide:notebook'],
                ['label' => 'Cerrar sesión', 'route' => 'app_logout', 'icon' => 'lucide:log-out'],
            ];
        }else{
            // Utilisateur NON CONNECTÉ : liens publics
            return [
                ['label' => 'Accueil',    'route' => 'app_home',     'icon' => 'home'],
                ['label' => 'Se connecter', 'route' => 'app_login', 'icon' => 'login'],
                ['label' => "S'inscrire", 'route' => 'app_register', 'icon' => 'login'],

            ];
        }
        
    }
}