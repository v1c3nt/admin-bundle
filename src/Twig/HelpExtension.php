<?php

namespace Vvbx\AdminBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Vvbx\AdminBundle\Help\HelpLink;

/**
 * {{ help_link('dons#la-déclaration-annuelle') }} : lien « En savoir plus » vers l'aide, vide sans le rôle.
 */
final class HelpExtension extends AbstractExtension
{
    public function __construct(private HelpLink $helpLink)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('help_link', $this->helpLink->html(...), ['is_safe' => ['html']]),
        ];
    }
}
