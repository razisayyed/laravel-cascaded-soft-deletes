<?php declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\LogicException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\RuntimeException;

use function PHPUnit\Framework\assertEquals;

/**
 * @covers \RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes
 */
class NodeTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass() : void
    {
        $schema = Capsule::schema();

        $schema->dropIfExists('categories');

        Capsule::disableQueryLog();

        $schema->create('pages', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });

        $schema->create('blocks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
        });

        $schema->create('plugins', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
        });

        Capsule::enableQueryLog();
    }

    public function setUp() : void
    {
        $pages = include __DIR__.'/data/pages.php';
        Capsule::table('pages')->insert($pages);

        $blocks = include __DIR__.'/data/blocks.php';
        Capsule::table('blocks')->insert($blocks);

        $plugins = include __DIR__.'/data/plugins.php';
        Capsule::table('plugins')->insert($plugins);

        Capsule::flushQueryLog();

        date_default_timezone_set('Asia/Hebron');
    }

    public function tearDown() : void
    {
        Capsule::table('pages')->truncate();
        Capsule::table('blocks')->truncate();
        Capsule::table('plugins')->truncate();
    }   

    public function testDelete()
    {
        $page = $this->findPage('page 1');

        $originalBlocksCount = $this->pageBlocksCount('page 1');

        $page->delete();

        assertEquals($this->pageBlocksCount('page 1'), 0);
        assertEquals($this->pageBlocksCount('page 1', true), $originalBlocksCount);

    }

    public function testTwoLevelDelete()
    {
        $page = $this->findPage('page 1');

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
        $page = $this->findPage('page 1');

        // this block must not be restored with page
        $this->findBlock('block 2 - page 1')->delete();
        $originalBlocksCount = $this->pageBlocksCount('page 1');
        assertEquals($originalBlocksCount, 1, 'wrong blocks count after deleting one block!');

        // this plugin must not be restored with page
        $this->findPlugin('plugin 1 - block 1 - page 1')->delete();
        $originalPluginsCount = $this->blockPluginsCount('block 1 - page 1');
        assertEquals($originalPluginsCount, 2, 'wrong plugins count after deleting one plugin!');
        
        sleep(1);

        $page->delete();

        assertEquals($this->pageBlocksCount('page 1'), 0, 'undeleted block(s) found after deleting page!');

        // $page = $this->findPage('page 1', true);

        $page->restore();

        assertEquals($this->pageBlocksCount('page 1'), 1, 'wrong blocks count after restoring page!');
        assertEquals($this->blockPluginsCount('block 1 - page 1'), 2, 'wrong plugins coung after restoring page!');
    }

    public function testMissingMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('getCascadedSoftDeletes function not found!');

        $q = new MissingMethodPage;

        $q->whereName('page 1')->first()->delete();

    }

    public function testMissingSoftDeletes()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('relationship blocks does not use SoftDeletes trait.');

        $q = new MissingSoftDeletesPage;

        $q->whereName('page 1')->first()->delete();

    }

    public function testCallbackCascadeDelete()
    {
        $q = new PageCallbackCascade;
        $page = $q->newQuery()->whereName('page 1')->first();

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
