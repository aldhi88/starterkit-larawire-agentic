<?php

namespace Aldhi88\StarterKit\Support\Cms;

enum CmsResource: string
{
    case Posts = 'posts';
    case PostCategories = 'post_categories';
    case Pages = 'pages';
    case PageCategories = 'page_categories';
    case Cards = 'cards';
    case CardCategories = 'card_categories';
    case Media = 'media';
    case Galleries = 'galleries';
    case Menus = 'menus';
    case MenuCategories = 'menu_categories';
    case Comments = 'comments';

    public function module(): string
    {
        return match ($this) {
            self::Posts, self::PostCategories => 'posts',
            self::Pages, self::PageCategories => 'pages',
            self::Cards, self::CardCategories => 'cards',
            self::Media, self::Galleries => 'media',
            self::Menus, self::MenuCategories => 'menus',
            self::Comments => 'comments',
        };
    }

    public function routePrefix(): string
    {
        return 'cms.'.$this->module().match ($this) {
            self::PostCategories, self::PageCategories, self::CardCategories, self::MenuCategories => '.categories',
            self::Galleries => '.galleries',
            default => '',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Posts => 'Postingan', self::PostCategories => 'Kategori Post',
            self::Pages => 'Halaman', self::PageCategories => 'Kategori Halaman',
            self::Cards => 'Card', self::CardCategories => 'Kategori Card',
            self::Media => 'Pustaka Media', self::Galleries => 'Galeri',
            self::Menus => 'Menu', self::MenuCategories => 'Kategori Menu',
            self::Comments => 'Komentar',
        };
    }

    public function singular(): string
    {
        return $this->label();
    }

    public function isContent(): bool
    {
        return in_array($this, [self::Posts, self::Pages, self::Cards], true);
    }

    public function isCategory(): bool
    {
        return in_array($this, [self::PostCategories, self::PageCategories, self::CardCategories], true);
    }

    public function kind(): ?string
    {
        return match ($this) {
            self::Posts, self::PostCategories => 'post', self::Pages, self::PageCategories => 'page',
            self::Cards, self::CardCategories => 'card', default => null,
        };
    }

    public function table(): string
    {
        return match ($this) {
            self::Posts, self::Pages, self::Cards => 'cms_contents',
            self::PostCategories, self::PageCategories, self::CardCategories => 'cms_categories',
            self::Media => 'cms_media', self::Galleries => 'cms_galleries',
            self::Menus => 'cms_menus', self::MenuCategories => 'cms_menu_categories', self::Comments => 'cms_comments',
        };
    }

    /** @return array<string,string> */
    public function statusOptions(): array
    {
        return match ($this) {
            self::Posts, self::Pages, self::Cards, self::Galleries => ['draft' => 'Draf', 'published' => 'Terbit'],
            self::Comments => ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'],
            default => ['active' => 'Aktif', 'inactive' => 'Nonaktif'],
        };
    }

    /** @return array<string,string> */
    public function tableFields(): array
    {
        $table = $this->table();
        $fields = ['name' => $table.'.'.($this === self::Comments ? 'author_name' : 'name'),
            'status' => $table.'.status', 'updated_at' => $table.'.updated_at', 'created_at' => $table.'.created_at'];
        if ($this->isContent() || $this === self::Menus) {
            $fields['category_id'] = $table.'.category_id';
        }
        if ($this !== self::Comments) {
            $fields['position'] = $table.'.position';
        }
        if ($this->isContent() || $this === self::Galleries) {
            $fields['slug'] = $table.'.slug';
        }

        if ($this === self::Comments) {
            $fields['content_id'] = $table.'.content_id';
            $fields['body'] = $table.'.body';
        }

        return $fields;
    }
}
