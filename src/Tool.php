<?php

declare(strict_types=1);

namespace DeepSeek\Agent;

readonly class Tool
{
    public function __construct(
        public string       $name,
        public string       $description,
        public array|Schema $schema,
        public \Closure     $execute,
        public int          $timeout = 30000,
        public int          $retries = 0,
        public bool         $strict = false,
        public bool         $required = false,
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
