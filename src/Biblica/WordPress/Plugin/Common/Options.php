<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\Common;

use Closure;

class Options
{
    private string $name;
    private string $group;
    private array $defaultValues;
    private Closure $sanitizeFunction;
    private ?array $values = null;

    public function __construct(string $name, string $group, array $defaultValues, callable $sanitizeFunction)
    {
        $this->name = $name;
        $this->group = $group;
        $this->defaultValues = $defaultValues;
        $this->sanitizeFunction = Closure::fromCallable($sanitizeFunction);

        $this->load();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getDefaultValues(): array
    {
        return $this->defaultValues;
    }

    public function getOption(string $key)
    {
        if (isset($this->toArray()[$key])) {
            $value = $this->toArray()[$key];
        } elseif (isset($this->defaultValues[$key])) {
            $value = $this->defaultValues[$key];
        } else {
            $value = null;
        }

        return $value;
    }

    public function setOption(string $key, $value): void
    {
        $this->values[$key] = $value;
    }

    public function save(): void
    {
        update_option($this->getName(), $this->values);
    }

    public function load(): void
    {
        $this->values = get_option($this->name, $this->defaultValues);

        if ($this->values === false || !is_array($this->values)) {
            $this->values = $this->defaultValues;
        }
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function sanitize($values): array
    {
        if (!is_array($values)) {
            return $this->defaultValues;
        }
        $sanitizedValues = $this->sanitizeFunction->call($this, $values);
        if (!is_array($sanitizedValues)) {
            return $this->defaultValues;
        }

        return $sanitizedValues;
    }

    public function sanitizeCheckBox($value): bool
    {
        if (!isset($value)) {
            $checked = false;
        } elseif (is_bool($value)) {
            $checked = $value;
        } else {
            $checked = true;
        }

        return $checked;
    }

    public function sanitizeInteger($value, $defaultValue): int
    {
        if (is_numeric($value)) {
            $integerValue = (int)$value;
        } else {
            $integerValue = $defaultValue;
        }

        return $integerValue;
    }

    public function register()
    {
        register_setting(
            $this->group,
            $this->name,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
                'default' => $this->defaultValues
            ]
        );
    }
}
