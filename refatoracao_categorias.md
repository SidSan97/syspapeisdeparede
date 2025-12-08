# Refatoração do Sistema de Categorias (Categorias + Subcategorias em Cascata)

Você possuía três tabelas:

-   **collection_arts** (categorias)
-   **collection_arts_subcategories** (subcategorias)
-   **collection_images** (imagens ligadas à subcategoria)

O objetivo é unificar categorias/subcategorias em uma única tabela
hierárquica usando **parent_id**, e manter imagens ligadas a qualquer
categoria.

------------------------------------------------------------------------

## 1. Nova tabela unificada: `collection_categories`

``` sql
CREATE TABLE `collection_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `image_cover` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `collection_categories`
  ADD CONSTRAINT `fk_collection_categories_parent_id`
  FOREIGN KEY (`parent_id`)
  REFERENCES `collection_categories` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
```

------------------------------------------------------------------------

## 2. Tabela de imagens ligada à categoria genérica

``` sql
CREATE TABLE `collection_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `collection_category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `path_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `collection_images`
  ADD CONSTRAINT `fk_collection_images_category`
  FOREIGN KEY (`collection_category_id`)
  REFERENCES `collection_categories` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
```

------------------------------------------------------------------------

## 3. Models no Laravel

### **Model: CollectionCategory**

``` php
class CollectionCategory extends Model
{
    protected $fillable = [
        'name',
        'image_cover',
        'parent_id',
    ];

    public function children()
    {
        return $this->hasMany(CollectionCategory::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(CollectionCategory::class, 'parent_id');
    }

    public function images()
    {
        return $this->hasMany(CollectionImage::class, 'collection_category_id');
    }
}
```

------------------------------------------------------------------------

### **Model: CollectionImage**

``` php
class CollectionImage extends Model
{
    protected $fillable = [
        'collection_category_id',
        'name',
        'path_name',
    ];

    public function category()
    {
        return $this->belongsTo(CollectionCategory::class, 'collection_category_id');
    }
}
```

------------------------------------------------------------------------

## 4. Exemplos de uso

### Criar categoria principal

``` php
$parent = CollectionCategory::create([
    'name' => 'Arte 3D'
]);
```

### Criar subcategoria

``` php
$sub = CollectionCategory::create([
    'name' => 'Modelos Low Poly',
    'parent_id' => $parent->id,
]);
```

### Adicionar imagem

``` php
$sub->images()->create([
    'name' => 'floresta',
    'path_name' => 'images/floresta.png'
]);
```

------------------------------------------------------------------------

## Benefícios

-   Uma única tabela para categorias e subcategorias\
-   Relação recursiva (pai/filho) via `parent_id`\
-   Cascata automática para subcategorias e imagens\
-   Permite estruturas hierárquicas infinitas\
-   Código mais limpo, organizado e escalável
