<?php

namespace App\Helpers;


class MyHelper
{
    public static function convertKeysToSnakeCase(array $input)
    {
        $output = [];

        foreach ($input as $key => $value) {
            // Convert camelCase to snake_case
            $snakeCaseKey = preg_replace_callback(
                '/([a-z])([A-Z])/',
                function ($matches) {
                    return strtolower($matches[1] . '_' . strtolower($matches[2]));
                },
                $key
            );

            $output[$snakeCaseKey] = $value;
        }

        return $output;
    }
}
