[![Tests](https://github.com/razisayyed/laravel-cascaded-soft-deletes/actions/workflows/php.yml/badge.svg)](https://github.com/razisayyed/laravel-cascaded-soft-deletes/actions/workflows/php.yml)
[![codecov](https://codecov.io/gh/razisayyed/laravel-cascaded-soft-deletes/branch/main/graph/badge.svg?token=8E48QF245M)](https://codecov.io/gh/razisayyed/laravel-cascaded-soft-deletes)
[![Total Downloads](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/downloads.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)
[![Latest Stable Version](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/v/stable.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)
[![Latest Unstable Version](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/v/unstable.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)
[![License](https://poser.pugx.org/razisayyed/laravel-cascaded-soft-deletes/license.svg)](https://packagist.org/packages/razisayyed/laravel-cascaded-soft-deletes)

This is a Laravel 8 package for cascding SoftDeletes delete/restore actions.

*   **Laravel 7.0** is supported since v0.1.0
*   **Laravel 8.0** is supported since v0.1.0

Although this project is completely free for use, I appreciate any support!

-   __[Donate via PayPal](https://www.paypal.me/RaziAlsayyed)__

__Contents:__

- [Features](#features)
- [Notes](#notes)
- [Installation](#installation)

Features
--------
*   Cascade soft delete for chosen relations
*   Cascade restore for chosen relations (only models with deleted_at >= restoredInstance->deleted_at value will be restored)
*   Ability to follow custom query
*   By default a cascade action will be added to default queue (upcomming version will add options to disable this feature or choose different queue)

Notes
-----
*   The idea of this package has been extracted from the fabulous package [laravel-nestedset](https://github.com/lazychaser/laravel-nestedset)
*   Because the package relies on deleted_at column to make the comparision when it cascades restore action, it is recommended to use ```$table->softDeletes('deleted_at', 6);``` in the migration files. Otherwise, you may restore a related model that has been deleted before the instance in a fraction of the second.


Installation
------------
To install the package, in terminal:

```
composer require razisayyed/laravel-cascaded-soft-deletes
```

### Setting up
to setup CascadedSoftDeletes you need to use the trait at the parent model and add a protected function that returns a list of the relations needed to be cascaded

```php
...
<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class Page extends Model {

    use SoftDeletes;
    use CascadedSoftDeletes;

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    protected function getCascadedSoftDeletes()
    {
        return ['blocks'];
    }

}
```
```php
<?php

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use \RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class Block extends Model {

    use SoftDeletes;
    use CascadedSoftDeletes;

    public function page() 
    {
        return $this->belongsTo(Page::class);
    }

}
```

### Requirements for the Parent & Child model classes

-   Both classes must use SoftDeletes trait.
-   Parent class must use CascadedSoftDeletes trait.
-   Parent class must implement **getCascadedSoftDeletes** method which must return a list of cascaded HasMany relations.

__WIP__

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
