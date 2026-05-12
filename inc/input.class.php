<?php

/**
 * Input normalization helper for Vehicle Scheduler.
 *
 * This helper centralizes type coercion and basic sanitization for plugin forms.
 * It must only normalize raw input values.
 *
 * Business validation must remain in each domain class.
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerInput
{
    /**
     * Returns a sanitized integer value from an input array.
     *
     * @param array       $source  Raw input source.
     * @param string      $key     Source key.
     * @param int         $default Default value when key is missing or invalid.
     * @param int|null    $min     Optional minimum accepted value.
     * @param int|null    $max     Optional maximum accepted value.
     *
     * @return int
     */
    public static function int(
        array $source,
        string $key,
        int $default = 0,
        ?int $min = null,
        ?int $max = null
    ): int {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = filter_var($source[$key], FILTER_VALIDATE_INT);
        if ($value === false) {
            return $default;
        }

        if ($min !== null && $value < $min) {
            return $min;
        }

        if ($max !== null && $value > $max) {
            return $max;
        }

        return (int)$value;
    }

    /**
     * Returns a normalized string from an input array.
     *
     * The value is trimmed and truncated to the requested maximum length.
     *
     * @param array  $source   Raw input source.
     * @param string $key      Source key.
     * @param int    $maxLen   Maximum allowed length.
     * @param string $default  Default value.
     *
     * @return string
     */
    public static function string(
        array $source,
        string $key,
        int $maxLen = 255,
        string $default = ''
    ): string {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = trim((string)$source[$key]);

        if ($maxLen > 0 && mb_strlen($value) > $maxLen) {
            $value = mb_substr($value, 0, $maxLen);
        }

        return $value;
    }

    /**
     * Returns a normalized text field from an input array.
     *
     * Text fields are trimmed but not stripped of punctuation or line breaks.
     *
     * @param array  $source   Raw input source.
     * @param string $key      Source key.
     * @param int    $maxLen   Maximum allowed length.
     * @param string $default  Default value.
     *
     * @return string
     */
    public static function text(
        array $source,
        string $key,
        int $maxLen = 65535,
        string $default = ''
    ): string {
        return self::string($source, $key, $maxLen, $default);
    }

    /**
     * Returns a GLPI-compatible boolean value as 0 or 1.
     *
     * @param array  $source   Raw input source.
     * @param string $key      Source key.
     * @param bool   $default  Default boolean value.
     *
     * @return int
     */
    public static function bool(array $source, string $key, bool $default = false): int
    {
        if (!array_key_exists($key, $source)) {
            return $default ? 1 : 0;
        }

        $value = $source[$key];

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return $value === 1 ? 1 : 0;
        }

        $normalized = mb_strtolower(trim((string)$value));

        return in_array($normalized, ['1', 'true', 'on', 'yes', 'sim'], true) ? 1 : 0;
    }

    /**
     * Returns a whitelisted enum value from an input array.
     *
     * @param array  $source   Raw input source.
     * @param string $key      Source key.
     * @param array  $allowed  Allowed values.
     * @param string $default  Default value when input is not allowed.
     *
     * @return string
     */
    public static function enum(
        array $source,
        string $key,
        array $allowed,
        string $default = ''
    ): string {
        $value = self::string($source, $key, 255, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Returns a normalized list of enum values from an input array.
     *
     * Accepts both a scalar value and an array of values.
     *
     * @param array $source Raw input source.
     * @param string $key Source key.
     * @param array $allowed Allowed values.
     * @param array $default Default values.
     *
     * @return array
     */
    public static function enumList(
        array $source,
        string $key,
        array $allowed,
        array $default = []
    ): array {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $rawValues = $source[$key];
        if (!is_array($rawValues)) {
            $rawValues = [$rawValues];
        }

        $normalized = [];
        foreach ($rawValues as $value) {
            $item = trim((string) $value);
            if ($item === '' || !in_array($item, $allowed, true)) {
                continue;
            }

            $normalized[$item] = $item;
        }

        return array_values($normalized);
    }

    /**
     * Returns a normalized date in Y-m-d format or null.
     *
     * @param array       $source   Raw input source.
     * @param string      $key      Source key.
     * @param string|null $default  Default value.
     *
     * @return string|null
     */
    public static function date(array $source, string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = trim((string)$source[$key]);
        if ($value === '') {
            return $default;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date instanceof DateTime || $date->format('Y-m-d') !== $value) {
            return $default;
        }

        return $value;
    }

    /**
     * Returns a normalized datetime in Y-m-d H:i:s format or null.
     *
     * Accepts values from native datetime-local fields and regular SQL-like
     * date-time strings.
     *
     * @param array       $source   Raw input source.
     * @param string      $key      Source key.
     * @param string|null $default  Default value.
     *
     * @return string|null
     */
    public static function datetime(array $source, string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = trim((string)$source[$key]);
        if ($value === '') {
            return $default;
        }

        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return $default;
    }
}
