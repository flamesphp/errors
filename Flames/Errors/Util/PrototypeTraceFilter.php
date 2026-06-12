<?php

declare(strict_types=1);

namespace Flames\Errors\Util;

use BadMethodCallException;
use Flames\Errors\Exception\Frame;
use Flames\Errors\Exception\FrameCollection;

/**
 * Hides Prototype / Delegate plumbing from stack traces so errors surface
 * closer to user code.
 */
final class PrototypeTraceFilter
{
    private const DELEGATE_MARKER = 'Collection/Prototype/Delegate.php';

    private const TRAIT_MARKER = 'Collection/Trait/Prototype.php';

    /** @var list<string> */
    private const DELEGATE_HIDDEN_FUNCTIONS = [
        'assertMemberAccessible',
        'resolveMethod',
        'resolveProperty',
    ];

    public static function filterFrames(FrameCollection $frames, \Throwable $exception): FrameCollection
    {
        return $frames->filter(
            static fn(Frame $frame): bool => !self::shouldHideFrame($frame, $exception)
        );
    }

    private static function shouldHideFrame(Frame $frame, \Throwable $exception): bool
    {
        if (self::isPrototypeTraitInternalFrame($frame)) {
            return true;
        }

        if (self::isPrototypePermissionError($exception) && self::isDelegateInternalFrame($frame, $exception)) {
            return true;
        }

        return false;
    }

    private static function isPrototypeTraitInternalFrame(Frame $frame): bool
    {
        $file = self::normalizePath($frame->getFile());
        if ($file === null || !str_contains($file, self::TRAIT_MARKER)) {
            return false;
        }

        $function = $frame->getFunction();

        if ($function === '__invokePrototypeHandler__') {
            return true;
        }

        return $function !== null && str_contains($function, '{closure');
    }

    private static function isDelegateInternalFrame(Frame $frame, \Throwable $exception): bool
    {
        $file = self::normalizePath($frame->getFile());
        if ($file === null || !str_contains($file, self::DELEGATE_MARKER)) {
            return false;
        }

        $function = $frame->getFunction();

        if ($function === null && $frame->getClass() === $exception::class) {
            return true;
        }

        return in_array($function, self::DELEGATE_HIDDEN_FUNCTIONS, true);
    }

    private static function isPrototypePermissionError(\Throwable $exception): bool
    {
        if (!$exception instanceof BadMethodCallException) {
            return false;
        }

        return str_starts_with($exception->getMessage(), 'Prototype cannot access');
    }

    private static function normalizePath(?string $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return str_replace('\\', '/', $file);
    }
}
