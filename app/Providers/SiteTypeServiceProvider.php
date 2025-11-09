<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\LoadBalancerMethod;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\Plugins\RegisterSiteType;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SiteTypes\NodeJS;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\PHPMyAdmin;
use App\SiteTypes\PHPSite;
use App\SiteTypes\Wordpress;
use Illuminate\Support\ServiceProvider;

class SiteTypeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->php();
        $this->phpBlank();
        $this->laravel();
        $this->nodeJS();
        $this->loadBalancer();
        $this->phpMyAdmin();
        $this->wordpress();

        $this->booted(function () {
            $this->sortAndGroupAllSiteTypeForms();
        });
    }

    private function sortAndGroupAllSiteTypeForms(): void
    {
        $types = config('site.types');

        foreach ($types as $typeKey => $typeConfig) {
            if (isset($typeConfig['form']) && is_array($typeConfig['form'])) {
                $types[$typeKey]['form'] = $this->sortAndGroupFields($typeConfig['form']);
            }
        }

        config(['site.types' => $types]);
    }

    private function sortAndGroupFields(array $fields): array
    {
        // Separate sections from regular fields
        $sections = [];
        $regularFields = [];
        $fieldsToGroup = [];

        foreach ($fields as $field) {
            if ($field['type'] === 'section') {
                $sections[$field['name']] = $field;
            } elseif (! empty($field['sectionName'])) {
                // Field belongs to a section
                if (! isset($fieldsToGroup[$field['sectionName']])) {
                    $fieldsToGroup[$field['sectionName']] = [];
                }
                $fieldsToGroup[$field['sectionName']][] = $field;
            } else {
                // Regular top-level field
                $regularFields[] = $field;
            }
        }

        // Group fields into their sections
        $orphanedFields = [];
        foreach ($fieldsToGroup as $sectionName => $sectionFields) {
            if (isset($sections[$sectionName])) {
                // Sort section's children by order
                usort($sectionFields, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

                // Merge with existing children or set new
                $existingChildren = $sections[$sectionName]['children'] ?? [];
                $sections[$sectionName]['children'] = array_merge($existingChildren, $sectionFields);
            } else {
                // Section doesn't exist, these are orphaned fields
                $orphanedFields = array_merge($orphanedFields, $sectionFields);
            }
        }

        // Merge regular fields and sections
        $allTopLevelFields = array_merge($regularFields, array_values($sections));

        // Sort top-level fields by order (stable sort)
        usort($allTopLevelFields, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        // Append orphaned fields at the end (sorted by order)
        if (! empty($orphanedFields)) {
            usort($orphanedFields, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
            $allTopLevelFields = array_merge($allTopLevelFields, $orphanedFields);
        }

        return $allTopLevelFields;
    }

    private function php(): void
    {
        RegisterSiteType::make(PHPSite::id())
            ->label('PHP')
            ->handler(PHPSite::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version')
                    ->order(10),
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control')
                    ->order(20),
                DynamicField::make('repository')
                    ->text()
                    ->component()
                    ->label('Repository')
                    ->order(30),
                DynamicField::make('branch')
                    ->component()
                    ->label('Branch')
                    ->order(40),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('e.g., public, www, dist (leave empty for root)')
                    ->description('The relative path of your website from /home/vito/your-domain/')
                    ->order(50),
                DynamicField::make('composer')
                    ->checkbox()
                    ->label('Run `composer install --no-dev`')
                    ->default(false)
                    ->order(60),
            ]))
            ->register();
    }

    private function phpBlank(): void
    {
        RegisterSiteType::make(PHPBlank::id())
            ->label('PHP Blank')
            ->handler(PHPBlank::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version')
                    ->order(10),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->placeholder('e.g., public, www, dist (leave empty for root)')
                    ->description('The relative path of your website from /home/vito/your-domain/')
                    ->order(20),
            ]))
            ->register();
    }

    private function laravel(): void
    {
        RegisterSiteType::make(Laravel::id())
            ->label('Laravel')
            ->handler(Laravel::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version')
                    ->order(10),
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control')
                    ->order(20),
                DynamicField::make('web_directory')
                    ->text()
                    ->label('Web Directory')
                    ->default('public')
                    ->placeholder('e.g., public, www, dist (leave empty for root)')
                    ->description('The relative path of your website from /home/vito/your-domain/')
                    ->order(30),
                DynamicField::make('repository')
                    ->text()
                    ->label('Repository')
                    ->placeholder('organization/repository')
                    ->order(40),
                DynamicField::make('branch')
                    ->text()
                    ->label('Branch')
                    ->default('main')
                    ->order(50),
                DynamicField::make('composer')
                    ->checkbox()
                    ->label('Run `composer install --no-dev`')
                    ->default(false)
                    ->order(60),
            ]))
            ->register();
        RegisterSiteFeature::make(Laravel::id(), 'modern-deployment')
            ->label('Modern Deployment (beta)')
            ->description('Enables zero downtime deployment and deployment rollbacks')
            ->register();
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'enable')
            ->label('Enable')
            ->handler(\App\SiteFeatures\ModernDeployment\Enable::class)
            ->register();
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'disable')
            ->label('Disable')
            ->handler(\App\SiteFeatures\ModernDeployment\Disable::class)
            ->register();
        RegisterSiteFeatureAction::make(Laravel::id(), 'modern-deployment', 'configuration')
            ->label('Configure')
            ->handler(\App\SiteFeatures\ModernDeployment\Configuration::class)
            ->register();
    }

    private function nodeJS(): void
    {
        RegisterSiteType::make(NodeJS::id())
            ->label('NodeJS with NPM')
            ->handler(NodeJS::class)
            ->form(DynamicForm::make([
                DynamicField::make('source_control')
                    ->component()
                    ->label('Source Control')
                    ->order(10),
                DynamicField::make('port')
                    ->text()
                    ->label('Port')
                    ->placeholder('3000')
                    ->description('On which port your app will be running')
                    ->order(20),
                DynamicField::make('repository')
                    ->text()
                    ->label('Repository')
                    ->placeholder('organization/repository')
                    ->description('Your package.json must have start and build scripts')
                    ->order(30),
                DynamicField::make('branch')
                    ->text()
                    ->label('Branch')
                    ->default('main')
                    ->order(40),
            ]))
            ->register();
    }

    public function loadBalancer(): void
    {
        RegisterSiteType::make(LoadBalancer::id())
            ->label('Load Balancer')
            ->handler(LoadBalancer::class)
            ->form(DynamicForm::make([
                DynamicField::make('method')
                    ->select()
                    ->label('Load Balancing Method')
                    ->options([
                        LoadBalancerMethod::IP_HASH->value,
                        LoadBalancerMethod::ROUND_ROBIN->value,
                        LoadBalancerMethod::LEAST_CONNECTIONS->value,
                    ])
                    ->order(10),
            ]))
            ->register();
    }

    public function phpMyAdmin(): void
    {
        RegisterSiteType::make(PHPMyAdmin::id())
            ->label('PHPMyAdmin')
            ->handler(PHPMyAdmin::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version')
                    ->order(10),
            ]))
            ->register();
    }

    public function wordpress(): void
    {
        RegisterSiteType::make(Wordpress::id())
            ->label('WordPress')
            ->handler(Wordpress::class)
            ->form(DynamicForm::make([
                DynamicField::make('php_version')
                    ->component()
                    ->label('PHP Version')
                    ->order(10),
                DynamicField::make('title')
                    ->text()
                    ->label('Site Title')
                    ->placeholder('My WordPress Site')
                    ->order(20),
                DynamicField::make('username')
                    ->text()
                    ->label('Admin Username')
                    ->placeholder('admin')
                    ->order(30),
                DynamicField::make('password')
                    ->text()
                    ->label('Admin Password')
                    ->order(40),
                DynamicField::make('email')
                    ->text()
                    ->label('Admin Email')
                    ->order(50),
                DynamicField::make('database')
                    ->text()
                    ->label('Database Name')
                    ->placeholder('wordpress')
                    ->order(60),
                DynamicField::make('database_user')
                    ->text()
                    ->label('Database User')
                    ->placeholder('wp_user')
                    ->order(70),
                DynamicField::make('database_password')
                    ->text()
                    ->label('Database Password')
                    ->order(80),
            ]))
            ->register();
    }
}
