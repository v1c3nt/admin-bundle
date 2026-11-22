<?php

namespace Vvbx\AdminBundle\Help;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\RouteMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Liens vers l'aide : menu, boutons des écrans, liens « En savoir plus ». Rien sans le rôle configuré.
 */
class HelpLink
{
    // ---- classe de l'item de menu épinglé en bas de la barre latérale (CSS du layout de l'app)
    public const MENU_CSS_CLASS = 'ea-sidebar-item-bottom';

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private AdminControllerRegistryInterface $controllerRegistry,
        private string $role,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->security->isGranted($this->role);
    }

    /**
     * Nom de route d'une action du contrôleur d'aide (index, page, image), préfixé par la route du dashboard.
     */
    public function routeName(string $action): string
    {
        return \sprintf('%s_help_%s', $this->controllerRegistry->getFirstDashboardRoute() ?? 'admin', $action);
    }

    public function url(string $target): string
    {
        [$page, $section] = array_pad(explode('#', $target, 2), 2, null);

        return $this->urlGenerator->generate($this->routeName('page'), ['page' => $page]).(null === $section ? '' : '#'.rawurlencode($section));
    }

    /**
     * Lien « En savoir plus » à ajouter à un texte (HTML), vide sans le rôle.
     */
    public function html(string $target, string $label = 'En savoir plus'): string
    {
        if (!$this->isAvailable()) {
            return '';
        }

        return \sprintf(' <a href="%s" target="_blank" rel="noopener"><i class="fas fa-question-circle"></i> %s</a>', htmlspecialchars($this->url($target)), htmlspecialchars($label));
    }

    /**
     * Bouton « Aide » d'une page EasyAdmin, ou null sans le rôle.
     */
    public function action(string $target, string $pageName): ?Action
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $action = Action::new('help', 'Aide', 'fas fa-question-circle')
            ->linkToUrl($this->url($target))
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);

        // ---- les pages d'édition et de détail n'affichent pas les actions globales, seulement celles de l'entité
        return Crud::PAGE_INDEX === $pageName ? $action->createAsGlobalAction() : $action;
    }

    /**
     * Item « Aide » du menu du dashboard, réservé au rôle configuré.
     */
    public function menuItem(string $label = 'Aide', string $icon = 'fa fa-question-circle'): RouteMenuItem
    {
        return MenuItem::linkToRoute($label, $icon, $this->routeName('index'))
            ->setCssClass(self::MENU_CSS_CLASS)
            ->setPermission($this->role);
    }
}
