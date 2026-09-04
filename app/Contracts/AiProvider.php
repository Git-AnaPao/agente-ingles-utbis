<?php

namespace App\Contracts;

interface AiProvider
{
    /**
     * Indica si hay una API key configurada para usar el proveedor.
     */
    public function isConfigured(): bool;

    /**
     * Evalua un audio de speaking: transcribe + determina si es correcto + feedback corto.
     *
     * @return array{transcription: string, is_correct: bool, feedback: string}
     */
    public function evaluateSpeakingAudio(
        string $audioBase64,
        string $mimeType,
        string $questionText,
        ?string $expectedAnswer = null,
        string $cefrLevel = 'A1',
    ): array;

    /**
     * Genera el feedback general de una leccion basado en todos los errores del alumno.
     *
     * @param float $score Porcentaje de acierto (0-100)
     * @param int $total Total de preguntas
     * @param int $correct Correctas
     * @param array $errors Lista de errores [{question, student_answer, feedback}]
     * @return string|null Párrafo de feedback general (null sin API key)
     */
    public function generateGeneralFeedback(
        float $score,
        int $total,
        int $correct,
        array $errors,
    ): ?string;

    /**
     * Genera la respuesta del tutor de chat IA.
     *
     * @param array $messages Historial [{role: 'user'|'assistant', content: string}]
     * @return string|null null si no hay API key configurada
     */
    public function chatReply(array $messages): ?string;
}
