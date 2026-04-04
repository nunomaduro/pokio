<?php

declare(strict_types=1);

namespace Pokio\Runtime\Fork;

use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * @internal
 */
final class ThrowableCapsule
{
    /**
     * Creates a new throwable capsule instance.
     */
    public function __construct(public Throwable $throwable)
    {
        //
    }

    /**
     * Serializes the throwable capsule.
     *
     * @return array{
     *     message: string,
     *     class: class-string<Throwable>,
     *     code: int,
     *     file: string,
     *     line: int,
     * }
     */
    public function __serialize(): array
    {
        return [
            'message' => $this->throwable->getMessage(),
            'code' => $this->throwable->getCode(),
            'file' => $this->throwable->getFile(),
            'line' => $this->throwable->getLine(),
            'trace' => $this->throwable->getTraceAsString(),
            'class' => $this->throwable::class,
        ];
    }

    /**
     * @param array{
     *     message: string,
     *     class: class-string<Throwable>,
     *     code: int,
     *     file: string,
     *     line: int,
     * } $data
     */
    public function __unserialize(array $data)
    {
        $reflection = new ReflectionClass($data['class']);
        $throwable = $reflection->newInstanceWithoutConstructor();

        try {
            $reflection = new ReflectionClass($throwable);

            $fileProp = $reflection->getProperty('message');
            $fileProp->setValue($throwable, $data['message']);

            $fileProp = $reflection->getProperty('file');
            $fileProp->setValue($throwable, $data['file']);

            $lineProp = $reflection->getProperty('line');
            $lineProp->setValue($throwable, $data['line']);
            // @codeCoverageIgnoreStart
        } catch (ReflectionException) {
            // Skip if properties can't be changed
            // @codeCoverageIgnoreEnd
        }

        throw $throwable;
    }
}
