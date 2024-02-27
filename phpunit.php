<?php

include __DIR__.'/vendor/autoload.php';

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'prfx_']);
$capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher);
$capsule->bootEloquent();
$capsule->setAsGlobal();

include __DIR__.'/tests/models/Page.php';
include __DIR__.'/tests/models/PageCallbackCascade.php';
include __DIR__.'/tests/models/MissingMethodPage.php';
include __DIR__.'/tests/models/MissingSoftDeletesPage.php';
include __DIR__.'/tests/models/MissingSoftDeletesBlock.php';
include __DIR__.'/tests/models/Block.php';
include __DIR__.'/tests/models/Plugin.php';
