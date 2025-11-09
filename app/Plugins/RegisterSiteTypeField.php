<?php

namespace App\Plugins;

use App\DTOs\DynamicField;

class RegisterSiteTypeField extends DynamicField
{
    private string $siteType;

    public static function make(string $siteType, string $fieldName): self
    {
        $instance = new self($fieldName);
        $instance->siteType = $siteType;

        return $instance;
    }

    public function register(): void
    {
        $types = config('site.types');

        if (! isset($types[$this->siteType])) {
            throw new \RuntimeException("Site type '{$this->siteType}' not found. Cannot register field.");
        }

        $fieldArray = $this->toArray();

        // Auto-increment order if not explicitly set (order === 0 means not set)
        if ($fieldArray['order'] === 0 || $fieldArray['order'] === 0.0) {
            $maxOrder = 0;
            foreach ($types[$this->siteType]['form'] as $existingField) {
                if (isset($existingField['order']) && $existingField['order'] > $maxOrder) {
                    $maxOrder = $existingField['order'];
                }
            }
            $fieldArray['order'] = $maxOrder + 10;
        }

        $types[$this->siteType]['form'][] = $fieldArray;

        config(['site.types' => $types]);
    }
}
