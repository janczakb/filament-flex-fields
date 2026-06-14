<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Unit;

use Bjanczak\FilamentFlexFields\Concerns\HasFlexFields;
use Bjanczak\FilamentFlexFields\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestModel extends Model
{
    use HasFlexFields;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}

class HasFlexFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table): void {
            $table->id();
            $table->json('flex_field_values')->nullable();
        });
    }

    public function test_it_initializes_casts_correctly(): void
    {
        $model = new TestModel;
        $model->initializeHasFlexFields();

        $this->assertSame('array', $model->getCasts()['flex_field_values']);
    }

    public function test_it_sets_and_gets_values(): void
    {
        $model = new TestModel;
        $model->initializeHasFlexFields();

        $model->setFlexFieldValue('bio', 'Developer from Poland');
        $model->setFlexFieldValue('skills.php', 'excellent');

        $this->assertSame('Developer from Poland', $model->getFlexFieldValue('bio'));
        $this->assertSame('excellent', $model->getFlexFieldValue('skills.php'));
        $this->assertSame([
            'bio' => 'Developer from Poland',
            'skills' => [
                'php' => 'excellent',
            ],
        ], $model->getFlexFieldValues());
    }

    public function test_it_sets_values_massively(): void
    {
        $model = new TestModel;
        $model->initializeHasFlexFields();

        $model->setFlexFieldValues([
            'rating' => 5,
            'color' => 'blue',
        ]);

        $this->assertSame(5, $model->getFlexFieldValue('rating'));
        $this->assertSame('blue', $model->getFlexFieldValue('color'));
    }

    public function test_it_queries_using_where_scopes(): void
    {
        TestModel::create([
            'flex_field_values' => ['role' => 'admin', 'status' => 'active'],
        ]);

        TestModel::create([
            'flex_field_values' => ['role' => 'user', 'status' => 'inactive'],
        ]);

        TestModel::create([
            'flex_field_values' => ['role' => 'moderator', 'status' => null],
        ]);

        // test whereFlexField
        $admins = TestModel::query()->whereFlexField('role', '=', 'admin')->get();
        $this->assertCount(1, $admins);
        $this->assertSame('admin', $admins->first()->getFlexFieldValue('role'));

        // test whereFlexFieldIn
        $staff = TestModel::query()->whereFlexFieldIn('role', ['admin', 'moderator'])->get();
        $this->assertCount(2, $staff);

        // test whereFlexFieldNull
        $noStatus = TestModel::query()->whereFlexFieldNull('status')->get();
        // moderator status is null, but also users who don't have the status key at all (SQLite behavior might vary, but for moderator it's explicitly null)
        $this->assertTrue($noStatus->contains(fn ($m): bool => $m->getFlexFieldValue('role') === 'moderator'));

        // test whereFlexFieldNotNull
        $hasStatus = TestModel::query()->whereFlexFieldNotNull('status')->get();
        $this->assertCount(2, $hasStatus);

        // test orWhereFlexField
        $filtered = TestModel::query()
            ->whereFlexField('role', '=', 'admin')
            ->orWhereFlexField('role', '=', 'user')
            ->get();
        $this->assertCount(2, $filtered);
    }
}
