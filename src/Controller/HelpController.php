<?php

namespace Vvbx\AdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Vvbx\AdminBundle\Help\HelpLink;
use Vvbx\AdminBundle\Help\HelpPages;

/**
 * Aide du back-office : pages et captures de l'app, versionnées avec son code, réservées au rôle configuré.
 */
#[AdminRoute('/aide', name: 'help')]
final class HelpController extends AbstractController
{
    public function __construct(private HelpPages $pages, private HelpLink $helpLink, private string $role)
    {
    }

    #[AdminRoute('/', name: 'index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted($this->role);
        $first = $this->pages->first() ?? throw $this->createNotFoundException('Aucune page d\'aide.');

        // ---- redirection plutôt que rendu : les liens relatifs des pages se résolvent depuis /aide/{page}
        return $this->redirectToRoute($this->helpLink->routeName('page'), ['page' => $first]);
    }

    // ---- les captures sont hors de public/ : relatives à la page, elles arrivent ici
    #[AdminRoute('/images/{fileName}', name: 'image')]
    public function image(string $fileName): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted($this->role);
        $path = $this->pages->imagePath($fileName) ?? throw $this->createNotFoundException();

        return new BinaryFileResponse($path);
    }

    #[AdminRoute('/{page}', name: 'page', options: ['requirements' => ['page' => '[a-z0-9-]+']])]
    public function page(string $page): Response
    {
        $this->denyAccessUnlessGranted($this->role);
        $content = $this->pages->content($page) ?? throw $this->createNotFoundException();

        return $this->render('@VvbxAdmin/help/page.html.twig', [
            'pages' => $this->pages->all(),
            'current' => $page,
            'content' => $content,
            'page_route' => $this->helpLink->routeName('page'),
        ]);
    }
}
