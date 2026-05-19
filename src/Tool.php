<?php

namespace DeepSeek\Wan;

class Tool
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array|Schema $schema,
        public readonly \Closure $execute,
        public readonly int $timeout = 30000,
        public readonly int $retries = 0,
        public readonly bool $strict = false,
        public readonly bool $required = false,
    ) {}

    public function toArray(): array
    {
        $schema = $this->schema instanceof Schema ? $this->schema->toArray() : $this->schema;

        return [
            'type' => 'function',
            'function' => [
                'name'        => $this->name,
                'description' => $this->description,
                'parameters'  => $schema,
            ],
        ];
    }
}
