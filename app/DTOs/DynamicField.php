<?php

namespace App\DTOs;

class DynamicField
{
    public function __construct(
        private string $name,
        private string $type = 'text',
        private string $label = '',
        private mixed $default = null,
        private ?string $placeholder = null,
        private ?string $description = null,
        private ?array $options = null,
        private ?array $link = null,
        private ?string $className = null,
        private ?array $children = null,
        private string $layout = 'collapsible',
        private bool $defaultOpen = false,
        private ?string $icon = null,
        private ?string $sectionName = null,
        private float $order = 0,
        private bool $locked = false,
        private mixed $enforcedValue = null,
        private ?string $columnDistribution = null,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function component(): self
    {
        $this->type = 'component';

        return $this;
    }

    public function text(): self
    {
        $this->type = 'text';

        return $this;
    }

    public function textarea(): self
    {
        $this->type = 'textarea';

        return $this;
    }

    public function select(): self
    {
        $this->type = 'select';

        return $this;
    }

    public function checkbox(): self
    {
        $this->type = 'checkbox';

        return $this;
    }

    public function alert(): self
    {
        $this->type = 'alert';

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function placeholder(?string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function options(?array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function link(string $label, string $url): self
    {
        $this->link = [
            'label' => $label,
            'url' => $url,
        ];

        return $this;
    }

    public function className(?string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function section(): self
    {
        $this->type = 'section';

        return $this;
    }

    public function children(array $children): self
    {
        foreach ($children as $child) {
            if (! $child instanceof self) {
                throw new \InvalidArgumentException('All children must be instances of DynamicField');
            }

            $childArray = $child->toArray();

            // Rows cannot contain sections
            if ($this->layout === 'row' && $childArray['type'] === 'section') {
                throw new \InvalidArgumentException('Rows cannot contain sections. Row "'.$this->name.'" cannot contain section "'.$childArray['name'].'"');
            }

            // Rows cannot contain rows
            if ($this->layout === 'row' && $childArray['type'] === 'section' && $childArray['layout'] === 'row') {
                throw new \InvalidArgumentException('Rows cannot be nested. Row "'.$this->name.'" cannot contain row "'.$childArray['name'].'"');
            }

            // Regular sections (collapsible/standard) cannot contain sections
            if ($this->layout !== 'row' && $childArray['type'] === 'section') {
                throw new \InvalidArgumentException('Sections cannot be nested. Section "'.$this->name.'" cannot contain section "'.$childArray['name'].'"');
            }
        }

        $this->children = $children;

        return $this;
    }

    public function inSection(string $sectionName): self
    {
        $this->sectionName = $sectionName;

        return $this;
    }

    public function order(float $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function locked(bool $locked = true): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function enforcedValue(mixed $value): self
    {
        $this->enforcedValue = $value;
        $this->locked = true; // Auto-enable locked when enforcedValue is set

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function layout(string $layout): self
    {
        if (! in_array($layout, ['collapsible', 'standard', 'row'])) {
            throw new \InvalidArgumentException('Layout must be one of: collapsible, standard, row');
        }

        $this->layout = $layout;

        return $this;
    }

    public function columnDistribution(string $distribution): self
    {
        $validDistributions = [
            '50/50', '33/33/33', '25/25/25/25',
            '40/60', '60/40', '30/70', '70/30', '20/80', '80/20',
        ];

        if (! in_array($distribution, $validDistributions)) {
            throw new \InvalidArgumentException('Invalid column distribution. Must be one of: '.implode(', ', $validDistributions));
        }

        $this->columnDistribution = $distribution;

        return $this;
    }

    public function defaultOpen(bool $open = true): self
    {
        $this->defaultOpen = $open;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Validate locked requires enforcedValue
        if ($this->locked && $this->enforcedValue === null) {
            throw new \InvalidArgumentException('Field "'.$this->name.'" is set to locked but has no enforcedValue. Locked fields must have an enforcedValue.');
        }

        return [
            'type' => $this->type,
            'name' => $this->name,
            'label' => $this->label,
            'default' => $this->default,
            'placeholder' => $this->placeholder,
            'description' => $this->description,
            'options' => $this->options,
            'link' => $this->link,
            'className' => $this->className,
            'children' => $this->children ? array_map(fn ($child) => $child->toArray(), $this->children) : null,
            'layout' => $this->layout,
            'defaultOpen' => $this->defaultOpen,
            'icon' => $this->icon,
            'sectionName' => $this->sectionName,
            'order' => $this->order,
            'locked' => $this->locked,
            'enforcedValue' => $this->enforcedValue,
            'columnDistribution' => $this->columnDistribution,
        ];
    }
}
