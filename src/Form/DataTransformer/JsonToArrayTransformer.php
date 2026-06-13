<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Преобразует JSON-строку в ассоциативный массив и обратно.
 */
final class JsonToArrayTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform($value): string
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        if (false === $json) {
            throw new TransformationFailedException('Failed to encode specifications to JSON.');
        }

        return $json;
    }// end transform()

    /**
     * @inheritDoc
     */
    public function reverseTransform($value): ?array
    {
        if ('' === $value || null === $value) {
            return [];
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $data = json_decode($value, true);
        if (!is_array($data)) {
            throw new TransformationFailedException('Invalid JSON for specifications.');
        }

        return $data;
    }// end reverseTransform()
}// end class
