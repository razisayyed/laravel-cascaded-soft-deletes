<?php declare(strict_types=1);

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Tests;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\LogicException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\RuntimeException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\Block;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\MissingMethodPage;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\MissingSoftDeletesPage;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\Page;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\PageCallbackCascade;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\Plugin;

use function PHPUnit\Framework\assertEquals;

/**
 * @covers \RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes
 * @covers \RaziAlsayyed\LaravelCascadedSoftDeletes\Jobs\CascadeSoftDeletes
 */
class CascadedSoftDeletesTest extends \Orchestra\Testbench\TestCase
{

    // use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

        // Schema::dropIfExists('plugins');
        // Schema::dropIfExists('blocks');
        // Schema::dropIfExists('pages');

        Schema::create('pages', function($table) {
            $table->id();
            $table->string('name');
            $table->softDeletes('deleted_at', 6);
        });

        Schema::create('blocks', function($table) {
            $table->id();
            $table->string('name');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->softDeletes('deleted_at', 6);
        });

        Schema::create('plugins', function($table) {
            $table->id();
            $table->string('name');
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->softDeletes('deleted_at', 6);
        });

        Page::insert([
            ['name' => 'page 1'],
            ['name' => 'page 2'],
            ['name' => 'page 3']
        ]);

        Block::insert([
            ['name' => 'block 1 - page 1', 'page_id' => 1],
            ['name' => 'block 2 - page 1', 'page_id' => 1],
            ['name' => 'block 1 - page 2', 'page_id' => 2],
            ['name' => 'block 2 - page 2', 'page_id' => 2]
        ]);

        Plugin::insert([
            ['id' => 1, 'name' => 'plugin 1 - block 1 - page 1', 'block_id' => 1],
            ['id' => 2, 'name' => 'plugin 2 - block 1 - page 1', 'block_id' => 1],
            ['id' => 3, 'name' => 'plugin 3 - block 1 - page 1', 'block_id' => 1],
            ['id' => 4, 'name' => 'plugin 1 - block 1 - page 2', 'block_id' => 3]
        ]);

    }

    public function tearDown() : void
    {
        Schema::drop('plugins');
        Schema::drop('blocks');
        Schema::drop('pages');

        parent::tearDown();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    // /**
    //  * Define database migrations.
    //  *
    //  * @return void
    //  */
    // protected function defineDatabaseMigrations()
    // {
    //     $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    // }

    protected function getApplicationTimezone($app)
    {
        return 'Asia/Hebron';
    }

    public function testDelete()
    {

        $page = Page::whereName('page 1')->first();

        $originalBlocksCount = $this->pageBlocksCount('page 1');

        $page->delete();

        assertEquals($this->pageBlocksCount('page 1'), 0);
        assertEquals($this->pageBlocksCount('page 1', true), $originalBlocksCount);

    }

    public function testTwoLevelDelete()
    {

        $page = Page::whereName('page 1')->first();

        $originalPluginsCount = $this->blockPluginsCount('block 1 - page 1');

        $page->delete();

        assertEquals($this->blockPluginsCount('block 1 - page 1'), 0);
        assertEquals($this->blockPluginsCount('block 1 - page 1', true), $originalPluginsCount);
    }

    public function testForceDelete()
    {
        $this->findPage('page 1')->forceDelete();

        assertEquals($this->findPage('page 1', true), null);

    }

    public function testRestore()
    {
        $page = Page::whereName('page 1')->first();

        // this block must not be restored with page
        $block = Block::whereName('block 2 - page 1')->first();
        $block->delete();

        $originalBlocksCount = $this->pageBlocksCount('page 1');
        assertEquals($originalBlocksCount, 1, 'wrong blocks count after deleting one block!');

        // this plugin must not be restored with page
        Plugin::whereName('plugin 1 - block 1 - page 1')->first()->delete();
        $originalPluginsCount = $this->blockPluginsCount('block 1 - page 1');
        assertEquals($originalPluginsCount, 2, 'wrong plugins count after deleting one plugin!');

        $page->delete();

        assertEquals($this->pageBlocksCount('page 1'), 0, 'undeleted block(s) found after deleting page!');

        $page->restore();

        assertEquals($this->pageBlocksCount('page 1'), 1, 'wrong blocks count after restoring page!');
        assertEquals($this->blockPluginsCount('block 1 - page 1'), 2, 'wrong plugins coung after restoring page!');

    }

    public function testMissingMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('getCascadedSoftDeletes function not found!');

        MissingMethodPage::whereName('page 1')->first()->delete();

    }

    public function testMissingSoftDeletes()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('relationship blocks does not use SoftDeletes trait.');

        MissingSoftDeletesPage::whereName('page 1')->first()->delete();

    }

    public function testCallbackCascadeDelete()
    {
        $page = PageCallbackCascade::whereName('page 1')->first();

        $page->delete();

        assertEquals($this->pageBlocksCount('page 1'), 2);
        assertEquals($this->blockPluginsCount('block 1 - page 1'), 3);

        assertEquals($this->pageBlocksCount('page 2'), 0);
        assertEquals($this->blockPluginsCount('block 1 - page 2'), 0);


    }

    public function testCallbackCascadeRestore()
    {
        $q = new PageCallbackCascade;
        $page = $q->newQuery()->whereName('page 1')->first();

        $page->delete();

        $page->restore();

        assertEquals($this->pageBlocksCount('page 2'), 2);
        assertEquals($this->blockPluginsCount('block 1 - page 2'), 1);


    }


    /**
     * @param $name
     *
     * @return \Page
     */
    public function findPage($name, $withTrashed = false) : ?Page
    {
        $q = new Page;

        $q = $withTrashed ? $q->withTrashed() : $q->newQuery();

        return $q->whereName($name)->first();
    }

    /**
     * @param $name
     *
     * @return \Block
     */
    public function findBlock($name, $withTrashed = false) : ?Block
    {
        $q = new Block;

        $q = $withTrashed ? $q->withTrashed() : $q->newQuery();

        return $q->whereName($name)->first();
    }
    /**
     * @param $name
     *
     * @return \Plugin
     */
    public function findPlugin($name, $withTrashed = false) : ?Plugin
    {
        $q = new Plugin;

        $q = $withTrashed ? $q->withTrashed() : $q->newQuery();

        return $q->whereName($name)->first();
    }

    /**
     * @param $name
     *
     * @return \Block
     */
    public function pageBlocksCount($pageName, $trashed = false) : int
    {
        if($trashed)
        {
            return $this->findPage($pageName, true)->blocks()->onlyTrashed()->count();
        }
        return $this->findPage($pageName, true)->blocks()->count();
    }

    /**
     * @param $name
     *
     * @return \Block
     */
    public function blockPluginsCount($blockName, $trashed = false) : int
    {
        if($trashed)
        {
            return $this->findBlock($blockName, true)->plugins()->onlyTrashed()->count();
        }
        return $this->findBlock($blockName, true)->plugins()->count();
    }

}
