<?php

namespace Vvbx\AdminBundle\Tests\Help;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vvbx\AdminBundle\Help\HelpLink;

final class HelpLinkTest extends TestCase
{
    public function testRoutesArePrefixedByTheDashboardRoute(): void
    {
        self::assertSame('gestion_help_page', $this->link(true, 'gestion')->routeName('page'));
    }

    public function testUrlKeepsTheSectionAsAnEncodedFragment(): void
    {
        self::assertSame('/admin/aide/mise-en-route#1-lidentit%C3%A9', $this->link(true)->url('mise-en-route#1-lidentité'));
        self::assertSame('/admin/aide/adhesions', $this->link(true)->url('adhesions'));
    }

    public function testNothingWithoutTheRole(): void
    {
        $link = $this->link(false);

        self::assertSame('', $link->html('adhesions'));
        self::assertNull($link->action('adhesions', Crud::PAGE_INDEX));
    }

    public function testGlobalActionOnIndexOnly(): void
    {
        $link = $this->link(true);

        self::assertTrue($link->action('adhesions', Crud::PAGE_INDEX)?->getAsDto()->isGlobalAction());
        // ---- EasyAdmin n'affiche pas les actions globales en édition
        self::assertFalse($link->action('adhesions', Crud::PAGE_EDIT)?->getAsDto()->isGlobalAction());
    }

    public function testMenuItemIsRestrictedToTheRole(): void
    {
        $item = $this->link(true)->menuItem()->getAsDto();

        self::assertSame('ROLE_MANAGER', $item->getPermission());
        self::assertSame(HelpLink::MENU_CSS_CLASS, $item->getCssClass());
        self::assertSame('admin_help_index', $item->getRouteName());
    }

    private function link(bool $granted, string $dashboardRoute = 'admin'): HelpLink
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn($granted);
        $registry = $this->createStub(AdminControllerRegistryInterface::class);
        $registry->method('getFirstDashboardRoute')->willReturn($dashboardRoute);
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $route, array $parameters): string => '/admin/aide/'.$parameters['page']);

        return new HelpLink($security, $urlGenerator, $registry, 'ROLE_MANAGER');
    }
}
