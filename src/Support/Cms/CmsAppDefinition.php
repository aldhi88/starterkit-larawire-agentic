<?php

namespace Aldhi88\StarterKit\Support\Cms;

class CmsAppDefinition
{
    /** @return array<string, mixed> */
    public static function configuration(): array
    {
        $group = static fn (string $label, string $icon, array $children): array => [
            'label' => $label, 'icon' => $icon, 'visible' => true, 'children' => $children,
        ];
        $link = static fn (string $label, string $route): array => [
            'label' => $label, 'route' => $route, 'visible' => true, 'landing' => true,
        ];

        return [
            '_starter_cms' => 1,
            'name' => 'CMS',
            'desc' => 'Manajemen konten website.',
            'icon' => 'world',
            'mods' => [
                'dashboard' => [
                    'name' => 'Dashboard CMS', 'desc' => 'Ringkasan konten CMS.',
                    'menus' => [$group('Dashboard', 'layout-dashboard', [$link('Beranda', 'cms.dashboard')])],
                ],
                'posts' => [
                    'name' => 'Konten', 'desc' => 'Kelola postingan dan kategori post.',
                    'menus' => [$group('Konten', 'clipboard-text', [
                        $link('Semua Postingan', 'cms.posts.index'),
                        $link('Post Baru', 'cms.posts.create'),
                        $link('Kategori Post', 'cms.posts.categories.index'),
                    ])],
                ],
                'pages' => [
                    'name' => 'Halaman', 'desc' => 'Kelola halaman dan kategori halaman.',
                    'menus' => [$group('Halaman', 'file-text', [
                        $link('Semua Halaman', 'cms.pages.index'),
                        $link('Halaman Baru', 'cms.pages.create'),
                        $link('Kategori Halaman', 'cms.pages.categories.index'),
                    ])],
                ],
                'cards' => [
                    'name' => 'Card', 'desc' => 'Kelola konten fleksibel dan kategori card.',
                    'menus' => [$group('Card', 'layout-cards', [
                        $link('Semua Card', 'cms.cards.index'),
                        $link('Card Baru', 'cms.cards.create'),
                        $link('Kategori Card', 'cms.cards.categories.index'),
                    ])],
                ],
                'media' => [
                    'name' => 'Media', 'desc' => 'Kelola pustaka media dan galeri.',
                    'menus' => [$group('Media', 'photo', [
                        $link('Pustaka Media', 'cms.media.index'),
                        $link('Galeri', 'cms.media.galleries.index'),
                    ])],
                ],
                'menus' => [
                    'name' => 'Menu', 'desc' => 'Kelola susunan dan kategori menu website.',
                    'menus' => [$group('Menu', 'menu-2', [
                        $link('Semua Menu', 'cms.menus.index'),
                        $link('Kategori Menu', 'cms.menus.categories.index'),
                    ])],
                ],
                'comments' => [
                    'name' => 'Komentar', 'desc' => 'Moderasi komentar postingan.',
                    'menus' => [[...$link('Komentar', 'cms.comments.index'), 'icon' => 'messages']],
                ],
            ],
        ];
    }
}
