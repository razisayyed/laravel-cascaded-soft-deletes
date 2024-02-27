[![Tests](https://github.com/razisayyed/laravel-cascaded-soft-deletes/actions/workflows/php.yml/badge.svg)](https://github.com/razisayyed/laravel-cascaded-soft-deletes/actions/workflows/php.yml)
[![codecov](https://codecov.io/gh/razisayyed/laravel-cascaded-soft-deletes/branch/main/graph/badge.svg?token=8E48QF245M)](https://codecov.io/gh/razisayyed/laravel-cascaded-soft-deletes)
[![Total Downloads](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/downloads.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)
[![Latest Stable Version](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/v/stable.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)
[![Latest Unstable Version](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/v/unstable.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/razisayyed/laravel-cascaded-soft-deletes/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/razisayyed/laravel-cascaded-soft-deletes/?branch=main)
[![License](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/license.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)

This is a Laravel 8 package for cascding SoftDeletes delete/restore actions.

*   **Laravel 7.0** is supported from v0.1.0 to v1.0.2
*   **Laravel 8.0** is supported from v0.1.0 to v1.0.2
*   **Laravel 9.0** is supported since v1.0.2
*   **Laravel 10.0** is supported since v1.0.4 (Thanks to [@mojowill](https://github.com/mojowill))

Although this project is completely free for use, I appreciate any support!

-   __[Donate via PayPal](https://www.paypal.me/RaziAlsayyed)__

__Contents:__

- [Features](#features)
- [Notes](#notes)
- [Installation](#installation)
- [License](#license)

Features
--------
*   Cascade soft delete for chosen relations
*   Cascade restore for chosen relations (only models with deleted_at >= restoredInstance->deleted_at value will be restored)
*   Ability to follow [custom query](#custom-queries)
*   By default all cascade action will be added to default queue, you can change this behaviour by [publishing package's config file](#publish-config).

Notes
-----
*   The idea of this package has been extracted from the fabulous package [laravel-nestedset](https://github.com/lazychaser/laravel-nestedset)
*   Because the package relies on deleted_at column to make the comparision when it cascades restore action, it is recommended to use ```$table->softDeletes('deleted_at', 6);``` in the migration files. Otherwise, you may restore a related model that has been deleted before the instance in a fraction of a second.


Installation
------------
To install the package, in terminal:

```
composer require razisayyed/laravel-cascaded-soft-deletes
```

### publish config
If you need to change config values for sync/async behaviour you may issue:

```
php artisan vendor:publish --provider="RaziAlsayyed\LaravelCascadedSoftDeletes\Providers\CascadedSoftDeletesProvider" --tag="config"

```

### Setting up

to setup CascadedSoftDeletes you need to use the trait at the parent model and define `$cascadedSoftDeletes` property or `getCascadedSoftDeletes()` method.

### Simple example with $cascadedSoftDeletes property

```php
...
<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class Page extends Model {

    use SoftDeletes;
    use CascadedSoftDeletes;

    protected $cascadedSoftDeletes = [ 'blocks' ];

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

}
```
```php
<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;

class Block extends Model {

    use SoftDeletes;

    public function page() 
    {
        return $this->belongsTo(Page::class);
    }

}
```

### Advanced example with getCascadedSoftDeletes and custom queries

You can also define a custom query to cascade soft deletes and restores through.

the following example describes a scenario where `Folder` is a model that uses `NodeTrait` from [laravel-nestedset](https://github.com/lazychaser/laravel-nestedset) class and each folder has many albums. `getCascadedSoftDeletes()` in the example will cascade soft deletes and restores to albums related to the folder and all its decendants.

```php
...
<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class Folder extends Model {

    use SoftDeletes;
    use NodeTrait;
    use CascadedSoftDeletes;

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    protected function getCascadedSoftDeletes()
    {
        return [
            'albums' => function() {
                return Album::whereHas('folder', function($q) {
                    $q->withTrashed()
                        ->where('_lft', '>=', $this->getLft())
                        ->where('_rgt', '<=', $this->getRgt());
                });  
            }
        ];
    }

}
```

### Requirements for the Parent & Child model classes

-   Both classes must use SoftDeletes trait.
-   Parent class must use CascadedSoftDeletes trait.
-   Parent class must define **$cascadedSoftDeletes** or implement **getCascadedSoftDeletes** method which must return a list of cascaded HasMany relations and/or custom Queries.

License
=======

MIT License

Copyright (c) 2022 Razi Alsayyed

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
