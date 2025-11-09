<?php

namespace App\Plugins;

class ModifySiteTypeField
{
    private string $siteType;

    private string $fieldName;

    private array $modifications = [];

    public function __construct(string $siteType, string $fieldName)
    {
        $this->siteType = $siteType;
        $this->fieldName = $fieldName;
    }

    public static function make(string $siteType, string $fieldName): self
    {
        return new self($siteType, $fieldName);
    }

    public function order(float $order): self
    {
        $this->modifications['order'] = $order;

        return $this;
    }

    public function locked(bool $locked = true): self
    {
        $this->modifications['locked'] = $locked;

        return $this;
    }

    public function enforcedValue(mixed $value): self
    {
        $this->modifications['enforcedValue'] = $value;
        $this->modifications['locked'] = true;

        return $this;
    }

    public function label(string $label): self
    {
        $this->modifications['label'] = $label;

        return $this;
    }

    public function description(string $description): self
    {
        $this->modifications['description'] = $description;

        return $this;
    }

    public function default(mixed $default): self
    {
        $this->modifications['default'] = $default;

        return $this;
    }

    public function inSection(string $sectionName): self
    {
        $this->modifications['sectionName'] = $sectionName;

        return $this;
    }

    public function register(): void
    {
        // Validate locked requires enforcedValue
        if (isset($this->modifications['locked']) && $this->modifications['locked'] === true) {
            if (! isset($this->modifications['enforcedValue']) || $this->modifications['enforcedValue'] === null) {
                throw new \InvalidArgumentException('Field "'.$this->fieldName.'" is set to locked but has no enforcedValue. Locked fields must have an enforcedValue.');
            }
        }

        $types = config('site.types');

        if (! isset($types[$this->siteType])) {
            throw new \RuntimeException("Site type '{$this->siteType}' not found. Cannot modify field.");
        }

        if (! isset($types[$this->siteType]['form'])) {
            throw new \RuntimeException("Site type '{$this->siteType}' has no form fields.");
        }

        $found = false;
        foreach ($types[$this->siteType]['form'] as &$field) {
            if ($field['name'] === $this->fieldName) {
                $field = array_merge($field, $this->modifications);
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new \RuntimeException("Field '{$this->fieldName}' not found in site type '{$this->siteType}'.");
        }

        config(['site.types' => $types]);
    }
}
