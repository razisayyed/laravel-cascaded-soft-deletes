<?php declare(strict_types=1);

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\LogicException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\RuntimeException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\Block;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\MissingMethodAndPropertyPage;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\MissingSoftDeletesPage;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\Page;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\PageCallbackCascade;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\PageMethod;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models\Plugin;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

/**
 * @covers \RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes
 * @covers \RaziAlsayyed\LaravelCascadedSoftDeletes\Jobs\CascadeSoftDeletes
 * @covers \RaziAlsayyed\LaravelCascadedSoftDeletes\Providers\CascadedSoftDeletesProvider
 */
class CascadedSoftDeletesTest extends \Orchestra\Testbench\TestCase
{

    use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

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
        // Schema::drop('plugins');
        // Schema::drop('blocks');
        // Schema::drop('pages');

        parent::tearDown();
    }

    /***
     * When testing inside of a Laravel installation, this is not needed
     */
    protected function getPackageProviders($app)
    {
        return [
            'RaziAlsayyed\LaravelCascadedSoftDeletes\Providers\CascadedSoftDeletesProvider'
        ];
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

    /**
     * @dataProvider pageModelProvider
     */
    public function testDelete($pageClass)
    {

        $page = (new $pageClass)->whereName('page 1')->first();

        $originalBlocksCount = $this->pageBlocksCount($pageClass, 'page 1');

        $page->delete();

        assertEquals($this->pageBlocksCount($pageClass, 'page 1'), 0);
        assertEquals($this->pageBlocksCount($pageClass, 'page 1', true), $originalBlocksCount);

    }

    /**
     * @dataProvider pageModelProvider
     */
    public function testTwoLevelDelete($pageClass)
    {

        $page = (new $pageClass)->whereName('page 1')->first();

        $originalPluginsCount = $this->blockPluginsCount('block 1 - page 1');

        $page->delete();

        assertEquals($this->blockPluginsCount('block 1 - page 1'), 0);
        assertEquals($this->blockPluginsCount('block 1 - page 1', true), $originalPluginsCount);
    }

    /**
     * @dataProvider pageModelProvider
     */
    public function testForceDelete($pageClass)
    {

        (new $pageClass)->whereName('page 1')->first()->forceDelete();

        assertNull(
            (new $pageClass)->whereName('page 1')->withTrashed()->first()
        );

    }

    /**
     * @dataProvider combinedProvider
     */
    public function testRestore($pageClass, $sync)
    {

        config()->set('cascaded-soft-deletes.queue_cascades_by_default', !$sync);

        $page = (new $pageClass)->whereName('page 1')->first();
        // $page = Page::whereName('page 1')->first();

        // this block must not be restored with page
        $block = Block::whereName('block 2 - page 1')->first();
        $block->delete();

        $originalBlocksCount = $this->pageBlocksCount($pageClass, 'page 1');
        assertEquals($originalBlocksCount, 1, 'wrong blocks count after deleting one block!');

        // this plugin must not be restored with page
        Plugin::whereName('plugin 1 - block 1 - page 1')->first()->delete();
        $originalPluginsCount = $this->blockPluginsCount('block 1 - page 1');
        assertEquals($originalPluginsCount, 2, 'wrong plugins count after deleting one plugin!');

        $page->delete();

        assertEquals($this->pageBlocksCount($pageClass, 'page 1'), 0, 'undeleted block(s) found after deleting page!');

        $page->restore();

        assertEquals($this->pageBlocksCount($pageClass, 'page 1'), 1, 'wrong blocks count after restoring page!');
        assertEquals($this->blockPluginsCount('block 1 - page 1'), 2, 'wrong plugins coung after restoring page!');

    }

    public function testMissingMethodAndProperty()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('neither getCascadedSoftDeletes function or cascaded_soft_deletes property exists!');

        MissingMethodAndPropertyPage::whereName('page 1')->first()->delete();
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

        assertEquals($this->pageBlocksCount(PageCallbackCascade::class, 'page 1'), 2);
        assertEquals($this->blockPluginsCount('block 1 - page 1'), 3);

        assertEquals($this->pageBlocksCount(PageCallbackCascade::class, 'page 2'), 0);
        assertEquals($this->blockPluginsCount('block 1 - page 2'), 0);


    }

    /**
     * @dataProvider dispatchProvider
     */
    public function testCallbackCascadeRestore($sync)
    {
        config()->set('cascaded-soft-deletes.queue_cascades_by_default', !$sync);

        $page = PageCallbackCascade::whereName('page 1')->first();

        $page->delete();

        $page->restore();

        assertEquals($this->pageBlocksCount(PageCallbackCascade::class, 'page 2'), 2);
        assertEquals($this->blockPluginsCount('block 1 - page 2'), 1);


    }

    public function dispatchProvider() {
        return [
            'async' => [ true ],
            'sync' => [ false ],
        ];
    }

    public function pageModelProvider() {
        return [
            'page with model' => [ PageMethod::class ],
            'page with property' => [ Page::class ]
        ];
    }

    public function combinedProvider() {
        return [
            'page with model sync'     => [PageMethod::class, true ],
            'page with model async'    => [PageMethod::class, false ],
            'page with property sync'  => [Page::class, true ],
            'page with property async' => [Page::class, false ],
        ];
    }


    /**
     * @param $name
     *
     * @return Model
     */
    public function findPage($name, $withTrashed = false) : ?Model
    {
        return $withTrashed ? 
            Page::withTrashed()->whereName($name)->first() :
            Page::whereName($name)->first();
    }

    /**
     * @param $name
     *
     * @return \Block
     */
    public function findBlock($name, $withTrashed = false) : ?Block
    {
        return $withTrashed ? 
            Block::withTrashed()->whereName($name)->first() :
            Block::whereName($name)->first();
    }
    /**
     * @param $name
     *
     * @return \Plugin
     */
    public function findPlugin($name, $withTrashed = false) : ?Plugin
    {
        return $withTrashed ? 
            Plugin::withTrashed()->whereName($name)->first() :
            Plugin::whereName($name)->first();
    }

    /**
     * @param $name
     *
     * @return \Block
     */
    public function pageBlocksCount($pageClass, $pageName, $trashed = false) : int
    {
        if($trashed)
        {
            return (new $pageClass)->withTrashed()->whereName($pageName)->first()->blocks()->onlyTrashed()->count();
        }
        return (new $pageClass)->withTrashed()->whereName($pageName)->first()->blocks()->count();
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
