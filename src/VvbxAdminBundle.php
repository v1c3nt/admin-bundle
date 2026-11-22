<?php

namespace Vvbx\AdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Vvbx\AdminBundle\Controller\HelpController;
use Vvbx\AdminBundle\Help\HelpActionsExtension;
use Vvbx\AdminBundle\Help\HelpLink;
use Vvbx\AdminBundle\Help\HelpPages;
use Vvbx\AdminBundle\Twig\HelpExtension;

/**
 * Socle EasyAdmin des apps de la suite. Aujourd'hui : l'aide intégrée.
 *
 * Pages Markdown de l'app (docs/aide/NN-slug.md), rendues dans le back-office sous /<dashboard>/aide ;
 * bouton « Aide » posé sur les écrans par l'attribut #[HelpPage] ; fonction Twig help_link().
 */
final class VvbxAdminBundle extends AbstractBundle
{
    protected string $extensionAlias = 'vvbx_admin';

    // ---- un identifiant sur chaque titre de l'aide, cible des boutons « Aide » (page#section)
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$builder->hasExtension('twig_extra')) {
            return;
        }

        $builder->prependExtensionConfig('twig_extra', [
            'commonmark' => [
                'heading_permalink' => [
                    'insert' => 'none',
                    'apply_id_to_heading' => true,
                    'id_prefix' => '',
                    'fragment_prefix' => '',
                ],
            ],
        ]);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('help')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')
                            ->info('Dossier des pages de l\'aide (NN-slug.md) et de leurs captures (images/)')
                            ->defaultValue('%kernel.project_dir%/docs/aide')
                        ->end()
                        ->scalarNode('role')
                            ->info('Rôle qui voit l\'aide, son menu et ses boutons')
                            ->defaultValue('ROLE_ADMIN')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{help: array{directory: string, role: string}} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services()->defaults()->autowire()->autoconfigure();

        $services->set(HelpPages::class)->arg('$directory', $config['help']['directory']);
        $services->set(HelpLink::class)->arg('$role', $config['help']['role']);
        $services->set(HelpActionsExtension::class)->tag(EasyAdminBundle::TAG_ACTIONS_EXTENSION);
        $services->set(HelpExtension::class)->tag('twig.extension');

        // ---- EasyAdmin ne crée les routes #[AdminRoute] que des contrôleurs portant ce tag
        $services->set(HelpController::class)
            ->arg('$role', $config['help']['role'])
            ->public()
            ->tag('controller.service_arguments')
            ->tag(EasyAdminBundle::TAG_ADMIN_ROUTE_CONTROLLER);

        // ---- syntaxe GitHub (tableaux, barré, liens auto) et identifiants de titres, absents du CommonMark par défaut de Twig
        $services->set(GithubFlavoredMarkdownExtension::class)->tag('twig.markdown.league_extension');
        $services->set(HeadingPermalinkExtension::class)->tag('twig.markdown.league_extension');
    }
}
