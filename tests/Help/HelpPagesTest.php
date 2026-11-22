<?php

namespace Vvbx\AdminBundle\Tests\Help;

use PHPUnit\Framework\TestCase;
use Vvbx\AdminBundle\Help\HelpPages;

final class HelpPagesTest extends TestCase
{
    private HelpPages $pages;

    protected function setUp(): void
    {
        $this->pages = new HelpPages(\dirname(__DIR__).'/Fixtures/aide');
    }

    public function testPagesAreListedInNumberOrderWithTheirTitle(): void
    {
        self::assertSame(['presentation' => 'Présentation', 'suite' => 'La suite'], $this->pages->all());
        self::assertSame('presentation', $this->pages->first());
    }

    public function testFileWithoutNumberIsNotAPage(): void
    {
        self::assertNull($this->pages->content('notes'));
    }

    public function testContentOfAPage(): void
    {
        self::assertStringContainsString('## 1. L\'identité', (string) $this->pages->content('suite'));
        self::assertNull($this->pages->content('inconnue'));
    }

    public function testImageMustBeASimpleFileName(): void
    {
        self::assertNotNull($this->pages->imagePath('capture.png'));
        self::assertNull($this->pages->imagePath('absente.png'));
        self::assertNull($this->pages->imagePath('../01-presentation.md'));
        self::assertNull($this->pages->imagePath('..%2F01-presentation.md'));
    }
}
