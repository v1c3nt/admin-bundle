<?php

namespace Vvbx\AdminBundle\Help;

/**
 * Pages de l'aide : fichiers Markdown « 01-presentation.md » du dossier configuré (docs/aide par défaut), dans l'ordre de leur numéro.
 * Le nom sans numéro sert d'adresse (« presentation »), le premier titre « # » de libellé.
 */
class HelpPages
{
    private const PAGE_PATTERN = '/^\d{2}-([a-z0-9-]+)\.md$/';
    private const IMAGE_PATTERN = '/^[a-z0-9-]+\.(png|jpg)$/';

    public function __construct(private string $directory)
    {
    }

    /**
     * @return array<string, string> titres indexés par adresse, dans l'ordre du sommaire
     */
    public function all(): array
    {
        $pages = [];
        foreach ($this->files() as $slug => $file) {
            $pages[$slug] = $this->titleOf((string) file_get_contents($file), $slug);
        }

        return $pages;
    }

    public function first(): ?string
    {
        return array_key_first($this->files());
    }

    public function content(string $slug): ?string
    {
        $file = $this->files()[$slug] ?? null;

        return null === $file ? null : (string) file_get_contents($file);
    }

    /**
     * Chemin d'une capture, seulement pour un nom de fichier simple : pas de sortie du dossier.
     */
    public function imagePath(string $fileName): ?string
    {
        $path = $this->directory.'/images/'.$fileName;

        return 1 === preg_match(self::IMAGE_PATTERN, $fileName) && is_file($path) ? $path : null;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @return array<string, string> fichiers indexés par adresse
     */
    private function files(): array
    {
        $files = [];
        foreach (scandir($this->directory) ?: [] as $entry) {
            if (1 === preg_match(self::PAGE_PATTERN, $entry, $matches)) {
                $files[$matches[1]] = $this->directory.'/'.$entry;
            }
        }

        return $files;
    }

    private function titleOf(string $markdown, string $slug): string
    {
        return 1 === preg_match('/^# (.+)$/m', $markdown, $matches) ? trim($matches[1]) : $slug;
    }
}
