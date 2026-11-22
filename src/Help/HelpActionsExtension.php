<?php

namespace Vvbx\AdminBundle\Help;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;

/**
 * Ajoute le bouton « Aide » aux écrans dont le contrôleur CRUD porte #[HelpPage] : aucun code dans configureActions().
 */
final class HelpActionsExtension implements ActionsExtensionInterface
{
    public function __construct(private HelpLink $helpLink)
    {
    }

    /**
     * @param AdminContext<object> $context
     */
    public function supports(AdminContext $context): bool
    {
        $controllerFqcn = $context->getCrud()?->getControllerFqcn();

        return null !== $controllerFqcn && [] !== HelpPage::of($controllerFqcn) && $this->helpLink->isAvailable();
    }

    /**
     * @param AdminContext<object> $context
     */
    public function extend(Actions $actions, AdminContext $context): void
    {
        $controllerFqcn = (string) $context->getCrud()?->getControllerFqcn();
        $done = [];
        foreach (HelpPage::of($controllerFqcn) as $helpPage) {
            foreach ($helpPage->pages as $pageName) {
                // ---- un seul bouton par page : le premier attribut qui la vise
                $action = isset($done[$pageName]) ? null : $this->helpLink->action($helpPage->target, $pageName);
                if (null !== $action) {
                    $actions->add($pageName, $action);
                    $done[$pageName] = true;
                }
            }
        }
    }
}
