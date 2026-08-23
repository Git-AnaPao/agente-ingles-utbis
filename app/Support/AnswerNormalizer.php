<?php

namespace App\Support;

class AnswerNormalizer
{
    /**
     * Normaliza una respuesta para comparación tolerante (case, puntuación, moneda).
     */
    public static function normalize(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/[\s]+/u', ' ', $normalized);
        $normalized = ltrim($normalized, '$€£¥¢');
        $normalized = rtrim($normalized, '.,!?;:');

        return $normalized;
    }
}