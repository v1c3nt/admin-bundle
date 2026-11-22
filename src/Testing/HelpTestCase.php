<?php

namespace Vvbx\AdminBundle\Testing;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vvbx\AdminBundle\Help\HelpLink;
use Vvbx\AdminBundle\Help\HelpPage;
use Vvbx\AdminBundle\Help\HelpPages;

/**
 * Cohérence de l'aide d'une app, à étendre dans ses tests : chaque page s'affiche, chaque capture, chaque lien
 * entre pages et chaque cible (#[HelpPage] des CRUD, help_link() des gabarits) existe.
 *
 * L'identifiant d'une section est son titre en minuscules, sans ponctuation, espaces remplacés par des tirets
 * (« 1. L'identité de l'association » → 1-lidentité-de-lassociation) : renommer un titre casse ce test, pas l'utilisateur.
 */
abstract class HelpTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    /**
     * Connecte un utilisateur qui a le rôle de l'aide.
     */
    abstract protected function loginAsHelpReader(KernelBrowser $client): void;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testEveryPageRendersWithItsImagesAndLinks(): void
    {
        $this->loginAsHelpReader($this->client);
        $help = static::getContainer()->get(HelpPages::class);
        $pages = $help->all();
        self::assertNotEmpty($pages, 'Aucune page d\'aide.');

        foreach (array_keys($pages) as $slug) {
            $this->client->request('GET', $this->pageUrl($slug));
            self::assertResponseIsSuccessful($slug);

            $markdown = (string) $help->content($slug);
            preg_match_all('/!\[[^\]]*\]\(images\/([^)]+)\)/', $markdown, $images);
            foreach ($images[1] as $image) {
                self::assertNotNull($help->imagePath($image), \sprintf('%s : capture « %s » absente', $slug, $image));
            }
            preg_match_all('/(?<!!)\[[^\]]*\]\(([a-z0-9-]+)(?:#([^)]+))?\)/u', $markdown, $links, \PREG_SET_ORDER);
            foreach ($links as $link) {
                self::assertArrayHasKey($link[1], $pages, \sprintf('%s : lien vers la page « %s » inexistante', $slug, $link[1]));
                if (isset($link[2])) {
                    $this->assertTargetExists($link[1].'#'.$link[2], $slug);
                }
            }
        }
    }

    public function testEveryScreenTargetExists(): void
    {
        $this->loginAsHelpReader($this->client);
        $targets = $this->screenTargets();

        foreach ($targets as $target => $origin) {
            $this->assertTargetExists($target, $origin);
        }
        // ---- garde-fou : un test qui ne vérifie rien passerait sans rien dire
        self::assertNotEmpty($targets, 'Aucune cible d\'aide trouvée (#[HelpPage] ou help_link()).');
    }

    public function testImagesCannotLeaveTheirDirectory(): void
    {
        $this->loginAsHelpReader($this->client);
        $link = static::getContainer()->get(HelpLink::class);
        $url = static::getContainer()->get(UrlGeneratorInterface::class)->generate($link->routeName('image'), ['fileName' => 'x']);

        $this->client->request('GET', substr($url, 0, -1).'..%2F..%2Fcomposer.json');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Cibles des écrans : attributs #[HelpPage] des CRUD et appels help_link('…') des gabarits.
     *
     * @return array<string, string> origine indexée par cible
     */
    protected function screenTargets(): array
    {
        $targets = [];
        foreach (static::getContainer()->get(AdminControllerRegistryInterface::class)->getAllCrudControllers() as $controllerFqcn) {
            foreach (HelpPage::of($controllerFqcn) as $helpPage) {
                $targets[$helpPage->target] = $controllerFqcn;
            }
        }

        $templates = static::getContainer()->getParameter('kernel.project_dir').'/templates';
        if (is_dir($templates)) {
            foreach ((new Finder())->files()->in($templates)->name('*.twig') as $file) {
                preg_match_all('/help_link\(\s*\'([^\']+)\'/', $file->getContents(), $matches);
                foreach ($matches[1] as $target) {
                    $targets[$target] = $file->getRelativePathname();
                }
            }
        }

        return $targets;
    }

    protected function assertTargetExists(string $target, string $origin): void
    {
        [$page, $section] = array_pad(explode('#', $target, 2), 2, null);
        $crawler = $this->client->request('GET', $this->pageUrl($page));
        self::assertResponseIsSuccessful(\sprintf('%s : page « %s » inexistante', $origin, $page));
        if (null !== $section) {
            self::assertCount(1, $crawler->filter(\sprintf('.help-body [id="%s"]', $section)), \sprintf('%s : section absente « %s »', $origin, $target));
        }
    }

    protected function pageUrl(string $slug): string
    {
        $link = static::getContainer()->get(HelpLink::class);

        return static::getContainer()->get(UrlGeneratorInterface::class)->generate($link->routeName('page'), ['page' => $slug]);
    }
}
