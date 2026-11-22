# vvbx/admin-bundle

Socle EasyAdmin commun aux applications Symfony de la suite vvbx. Aujourd'hui : l'**aide intégrée** au back-office.

- Pages Markdown versionnées avec l'app, rendues dans la mise en page EasyAdmin (sommaire, tableaux, captures).
- Bouton « Aide » sur un écran par un attribut sur son contrôleur CRUD : aucun code dans `configureActions()`.
- Lien « En savoir plus » dans un gabarit : `{{ help_link('page#section') }}`.
- Une classe de test vérifie que chaque page, capture, lien et cible existe.

Le contenu reste dans l'app ; le bundle ne fournit que la mécanique. La connexion SSO et l'identité de la structure sont dans [`vvbx/suite-bundle`](https://github.com/v1c3nt/suite-bundle), qui ne dépend pas d'EasyAdmin.

## Installation

```bash
composer require vvbx/admin-bundle
```

```php
// config/bundles.php
Vvbx\AdminBundle\VvbxAdminBundle::class => ['all' => true],
```

```yaml
# config/packages/vvbx_admin.yaml (facultatif)
vvbx_admin:
    help:
        role: ROLE_ADMIN                               # qui voit l'aide, son menu et ses boutons
        directory: '%kernel.project_dir%/docs/aide'    # pages et captures
```

Le bundle déclare les extensions CommonMark (syntaxe GitHub, identifiant sur chaque titre) et la configuration `twig_extra.commonmark` nécessaires : rien à ajouter dans l'app.

## Les pages

```
docs/aide/
    01-presentation.md
    02-mise-en-route.md
    images/
        mise-en-route-comptes.png
```

- Le numéro fixe l'ordre du sommaire, le nom sans numéro l'adresse (`/<dashboard>/aide/mise-en-route`), le premier titre `#` le libellé.
- Liens entre pages : `[les comptes](mise-en-route#2-les-comptes)` ; captures : `![Les comptes](images/mise-en-route-comptes.png)` (`[a-z0-9-]+.png|jpg`).
- L'identifiant d'une section est son titre en minuscules, sans ponctuation, espaces remplacés par des tirets : « 2. Les comptes » → `2-les-comptes`, « 1. L'identité » → `1-lidentité`.

## Menu, boutons et liens

```php
// DashboardController::configureMenuItems()
yield $this->helpLink->menuItem();   // Vvbx\AdminBundle\Help\HelpLink, réservé au rôle configuré
```

L'item porte la classe `HelpLink::MENU_CSS_CLASS` (`ea-sidebar-item-bottom`) : à l'app de l'épingler en bas de la barre latérale si elle le souhaite.

```php
use Vvbx\AdminBundle\Help\HelpPage;

#[HelpPage('adhesions')]                                          // bouton sur la liste
#[HelpPage('mise-en-route#4-la-personnalisation', pages: [Crud::PAGE_EDIT])]
class MembershipCrudController extends AbstractCrudController
```

- Un CRUD sans attribut prend ceux de son parent le plus proche qui en porte : une sous-liste filtrée (`class AdhesionsEnCoursCrudController extends MembershipCrudController`) garde le bouton de sa liste, ou déclare le sien, qui remplace ceux du parent.
- Sur la liste, le bouton est une action globale ; ailleurs, une action de l'entité (EasyAdmin n'affiche pas les actions globales en édition ni en détail).
- Sans le rôle configuré : ni menu, ni bouton, ni lien, et les pages répondent 403.

## Tests de l'app

```php
use Vvbx\AdminBundle\Testing\HelpTestCase;

class HelpTest extends HelpTestCase
{
    protected function loginAsHelpReader(KernelBrowser $client): void
    {
        $client->loginUser(/* un utilisateur qui a le rôle de l'aide */);
    }
}
```

`HelpTestCase` vérifie que chaque page s'affiche, que chaque capture et chaque lien entre pages (et sa section) existent, que chaque cible des attributs `#[HelpPage]` et des `help_link('…')` des gabarits existe, et qu'une capture ne sort pas de son dossier. Renommer un titre casse le test, pas l'utilisateur.

## Développement

```bash
composer test
composer stan    # PHPStan niveau 6
composer cs      # php-cs-fixer @Symfony (composer cs:fix pour appliquer)
```
