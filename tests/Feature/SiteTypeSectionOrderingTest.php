<?php

namespace Tests\Feature;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Facades\SSH;
use App\Plugins\ModifySiteTypeField;
use App\Plugins\RegisterSiteType;
use App\Plugins\RegisterSiteTypeField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTypeSectionOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fields_are_sorted_by_order(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-order')
            ->label('Test Order')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('field_c')->text()->label('Field C')->order(30),
                DynamicField::make('field_a')->text()->label('Field A')->order(10),
                DynamicField::make('field_b')->text()->label('Field B')->order(20),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-order']['form'];

        $this->assertEquals('field_a', $form[0]['name']);
        $this->assertEquals('field_b', $form[1]['name']);
        $this->assertEquals('field_c', $form[2]['name']);
    }

    public function test_section_groups_fields_by_section_name(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-sections')
            ->label('Test Sections')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('title')->text()->label('Site Title')->order(10),
                DynamicField::make('advanced')->section()->label('Advanced Options')->order(100),
                DynamicField::make('cache_enabled')->checkbox()->label('Enable Cache')->inSection('advanced')->order(20),
                DynamicField::make('debug_mode')->checkbox()->label('Debug Mode')->inSection('advanced')->order(10),
                DynamicField::make('domain_suffix')->text()->label('Domain Suffix')->order(50),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-sections']['form'];

        // Verify top-level field ordering
        $this->assertEquals('title', $form[0]['name']);
        $this->assertEquals('domain_suffix', $form[1]['name']);
        $this->assertEquals('advanced', $form[2]['name']);

        // Verify section has children sorted by order
        $this->assertArrayHasKey('children', $form[2]);
        $this->assertCount(2, $form[2]['children']);
        $this->assertEquals('debug_mode', $form[2]['children'][0]['name']);
        $this->assertEquals('cache_enabled', $form[2]['children'][1]['name']);

        // Verify property names use new convention
        $this->assertEquals(10, $form[0]['order']);
        $this->assertEquals('advanced', $form[2]['children'][0]['sectionName']);
    }

    public function test_orphaned_fields_are_appended_at_end(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-orphaned')
            ->label('Test Orphaned')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('title')->text()->label('Title')->order(10),
                DynamicField::make('orphan')->text()->label('Orphan')->inSection('nonexistent')->order(50),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-orphaned']['form'];

        $this->assertCount(2, $form);
        $this->assertEquals('title', $form[0]['name']);
        $this->assertEquals('orphan', $form[1]['name']);
    }

    public function test_plugin_field_auto_increments_order(): void
    {
        SSH::fake();

        RegisterSiteTypeField::make('wordpress', 'redis_enabled')
            ->checkbox()
            ->label('Enable Redis')
            ->register();

        $types = config('site.types');
        $wordpressForm = $types['wordpress']['form'];

        $redisField = collect($wordpressForm)->firstWhere('name', 'redis_enabled');

        // WordPress last field is at order 80, so new field should be 90
        $this->assertNotNull($redisField);
        $this->assertEquals(90, $redisField['order']);
    }

    public function test_plugin_field_respects_explicit_order(): void
    {
        SSH::fake();

        RegisterSiteTypeField::make('wordpress', 'cache_enabled')
            ->checkbox()
            ->label('Enable Cache')
            ->order(15)
            ->register();

        $types = config('site.types');
        $wordpressForm = $types['wordpress']['form'];

        $cacheField = collect($wordpressForm)->firstWhere('name', 'cache_enabled');

        $this->assertNotNull($cacheField);
        $this->assertEquals(15, $cacheField['order']);
    }

    public function test_locked_field_with_enforced_value(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-locked')
            ->label('Test Locked')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('environment')
                    ->text()
                    ->label('Environment')
                    ->locked()
                    ->enforcedValue('production'),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-locked']['form'];

        $this->assertTrue($form[0]['locked']);
        $this->assertEquals('production', $form[0]['enforcedValue']);
    }

    public function test_modify_field_changes_section_and_order(): void
    {
        SSH::fake();

        ModifySiteTypeField::make('wordpress', 'title')
            ->inSection('basic_info')
            ->order(5)
            ->register();

        $types = config('site.types');
        $wordpressForm = $types['wordpress']['form'];

        $titleField = collect($wordpressForm)->firstWhere('name', 'title');

        $this->assertEquals('basic_info', $titleField['sectionName']);
        $this->assertEquals(5, $titleField['order']);
    }

    public function test_section_layout_types(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-layouts')
            ->label('Test Layouts')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('collapsible_section')
                    ->section()
                    ->label('Collapsible Section')
                    ->layout('collapsible'),
                DynamicField::make('standard_section')
                    ->section()
                    ->label('Standard Section')
                    ->layout('standard'),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-layouts']['form'];

        $this->assertEquals('collapsible', $form[0]['layout']);
        $this->assertEquals('standard', $form[1]['layout']);
    }

    public function test_section_children_are_sorted_within_section(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-child-order')
            ->label('Test Child Order')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('advanced')->section()->label('Advanced')->order(100),
                DynamicField::make('field_c')->text()->inSection('advanced')->order(30),
                DynamicField::make('field_a')->text()->inSection('advanced')->order(10),
                DynamicField::make('field_b')->text()->inSection('advanced')->order(20),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-child-order']['form'];

        $section = $form[0];
        $this->assertEquals('advanced', $section['name']);
        $this->assertCount(3, $section['children']);
        $this->assertEquals('field_a', $section['children'][0]['name']);
        $this->assertEquals('field_b', $section['children'][1]['name']);
        $this->assertEquals('field_c', $section['children'][2]['name']);
    }

    public function test_multiple_orphaned_fields_are_sorted_by_order(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-multi-orphaned')
            ->label('Test Multi Orphaned')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('title')->text()->label('Title')->order(10),
                DynamicField::make('orphan_2')->text()->inSection('missing')->order(30),
                DynamicField::make('orphan_1')->text()->inSection('missing')->order(20),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-multi-orphaned']['form'];

        $this->assertEquals('title', $form[0]['name']);
        $this->assertEquals('orphan_1', $form[1]['name']);
        $this->assertEquals('orphan_2', $form[2]['name']);
    }

    public function test_row_layout_with_column_distribution(): void
    {
        SSH::fake();

        RegisterSiteType::make('test-row')
            ->label('Test Row')
            ->handler(\App\SiteTypes\PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('server_config')
                    ->section()
                    ->label('Server Configuration')
                    ->layout('row')
                    ->columnDistribution('60/40')
                    ->children([
                        DynamicField::make('server_name')->text()->label('Server Name'),
                        DynamicField::make('server_port')->text()->label('Port'),
                    ]),
            ]))
            ->register();

        $types = config('site.types');
        $form = $types['test-row']['form'];

        $this->assertEquals('row', $form[0]['layout']);
        $this->assertEquals('60/40', $form[0]['columnDistribution']);
        $this->assertCount(2, $form[0]['children']);
        $this->assertEquals('server_name', $form[0]['children'][0]['name']);
        $this->assertEquals('server_port', $form[0]['children'][1]['name']);
    }

    public function test_row_cannot_contain_sections(): void
    {
        SSH::fake();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rows cannot contain sections');

        DynamicField::make('my_row')
            ->section()
            ->layout('row')
            ->children([
                DynamicField::make('nested_section')->section(),
            ]);
    }

    public function test_rows_cannot_be_nested(): void
    {
        SSH::fake();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rows cannot be nested');

        DynamicField::make('outer_row')
            ->section()
            ->layout('row')
            ->children([
                DynamicField::make('inner_row')->section()->layout('row'),
            ]);
    }

    public function test_sections_cannot_be_nested(): void
    {
        SSH::fake();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sections cannot be nested');

        DynamicField::make('outer_section')
            ->section()
            ->layout('collapsible')
            ->children([
                DynamicField::make('inner_section')->section()->layout('standard'),
            ]);
    }
}
