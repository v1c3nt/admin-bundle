<?php

namespace Vvbx\AdminBundle\Help;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * Bouton « Aide » d'un écran EasyAdmin, posé sur le contrôleur CRUD :
 * #[HelpPage('adhesions')], ou #[HelpPage('mise-en-route#1-lidentité-de-lassociation', pages: [Crud::PAGE_EDIT])].
 *
 * La cible est « page » ou « page#section » (identifiant du titre, voir HelpTestCase).
 * Répétable : une cible différente par page EasyAdmin.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class HelpPage
{
    /**
     * @param list<string> $pages pages EasyAdmin qui affichent le bouton (Crud::PAGE_*)
     */
    public function __construct(
        public readonly string $target,
        public readonly array $pages = [Crud::PAGE_INDEX],
    ) {
    }

    /**
     * @param class-string $controllerFqcn
     *
     * @return list<self>
     */
    public static function of(string $controllerFqcn): array
    {
        return array_map(
            static fn (\ReflectionAttribute $attribute): self => $attribute->newInstance(),
            (new \ReflectionClass($controllerFqcn))->getAttributes(self::class),
        );
    }
}
