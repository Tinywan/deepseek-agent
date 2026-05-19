<?php

namespace DeepSeek\Wan;

class Schema
{
    private string $type;
    private ?string $description = null;
    private bool $isRequired = false;

    /** @var Schema[]|null */
    private ?array $properties = null;

    /** @var string[]|null */
    private ?array $requiredFields = null;

    private ?Schema $items = null;

    /** @var array|null */
    private ?array $enumValues = null;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    // -- Static factories --

    public static function string(): self
    {
        return new self('string');
    }

    public static function number(): self
    {
        return new self('number');
    }

    public static function integer(): self
    {
        return new self('integer');
    }

    public static function boolean(): self
    {
        return new self('boolean');
    }

    /** @param array<string, Schema> $properties */
    public static function object(array $properties): self
    {
        $instance = new self('object');
        $instance->properties = $properties;
        return $instance;
    }

    public static function array(Schema $items): self
    {
        $instance = new self('array');
        $instance->items = $items;
        return $instance;
    }

    /** @param array<int, string> $values */
    public static function enum(array $values): self
    {
        $instance = new self('string');
        $instance->enumValues = $values;
        return $instance;
    }

    // -- Chainable modifiers --

    public function describe(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function required(): self
    {
        $this->isRequired = true;
        return $this;
    }

    // -- Compilation --

    public function toArray(): array
    {
        $schema = ['type' => $this->type];

        if ($this->description !== null) {
            $schema['description'] = $this->description;
        }

        if ($this->properties !== null) {
            $schema['properties'] = [];
            $required = [];

            foreach ($this->properties as $name => $prop) {
                $schema['properties'][$name] = $prop->toArray();
                if ($prop->isRequired) {
                    $required[] = $name;
                }
            }

            if ($required) {
                $schema['required'] = $required;
            }
        }

        if ($this->items !== null) {
            $schema['items'] = $this->items->toArray();
        }

        if ($this->enumValues !== null) {
            $schema['enum'] = $this->enumValues;
        }

        return $schema;
    }
}
