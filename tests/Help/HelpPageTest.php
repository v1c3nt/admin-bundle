<?php

namespace Vvbx\AdminBundle\Tests\Help;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use PHPUnit\Framework\TestCase;
use Vvbx\AdminBundle\Help\HelpPage;

#[HelpPage('adhesions')]
#[HelpPage('mise-en-route#1-lidentité', pages: [Crud::PAGE_EDIT])]
final class HelpPageTest extends TestCase
{
    public function testAttributesAreReadFromTheController(): void
    {
        $helpPages = HelpPage::of(self::class);

        self::assertCount(2, $helpPages);
        self::assertSame('adhesions', $helpPages[0]->target);
        self::assertSame([Crud::PAGE_INDEX], $helpPages[0]->pages);
        self::assertSame([Crud::PAGE_EDIT], $helpPages[1]->pages);
    }

    public function testNoAttribute(): void
    {
        self::assertSame([], HelpPage::of(TestCase::class));
    }
}
