<?php
/**
 * ErrorHandler - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Flames\Errors\Exception;

use Flames\Errors\Inspector\InspectorInterface;
use Flames\Errors\Util\TemplateHelper;

/**
 * @internal
 */
class Formatter
{
    /**
     * Returns all basic information about the exception in a simple array
     * for further convertion to other languages
     * @param  InspectorInterface $inspector
     * @param  bool               $shouldAddTrace
     * @param  array<callable>    $frameFilters
     * @return array
     */
    public static function formatExceptionAsDataArray(InspectorInterface $inspector, $shouldAddTrace, array $frameFilters = [])
    {
        $exception = $inspector->getException();
        $response = [
            'type'    => get_class($exception),
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ];

        if ($shouldAddTrace) {
            $frames    = $inspector->getFrames($frameFilters);
            $frameData = [];

            foreach ($frames as $frame) {
                /** @var Frame $frame */
                $frameData[] = [
                    'file'     => $frame->getFile(),
                    'line'     => $frame->getLine(),
                    'function' => $frame->getFunction(),
                    'class'    => $frame->getClass(),
                    'args'     => $frame->getArgs(),
                ];
            }

            $response['trace'] = $frameData;
        }

        return $response;
    }

    public static function formatExceptionPlain(InspectorInterface $inspector)
    {
        $message = $inspector->getException()->getMessage();
        $frames = $inspector->getFrames();

        $plain = $inspector->getExceptionName();
        $plain .= ' thrown with message "';
        $plain .= $message;
        $plain .= '"'."\n\n";

        $plain .= "Stacktrace:\n";
        $pathHelper = new TemplateHelper();
        foreach ($frames as $i => $frame) {
            $plain .= "#". (count($frames) - $i - 1). " ";
            $plain .= $frame->getClass() ?: '';
            $plain .= $frame->getClass() && $frame->getFunction() ? ":" : "";
            $plain .= $frame->getFunction() ?: '';
            $plain .= ' in ';
            if ($frame->getFile()) {
                $plain .= $pathHelper->frameFileLabel($frame->getFile(), (int) $frame->getLine());
                if ($pathHelper->frameFileLine($frame->getFile(), (int) $frame->getLine()) !== null) {
                    $plain .= ':' . (int) $frame->getLine();
                }
            } else {
                $plain .= '<#unknown>';
            }
            $plain .= "\n";
        }

        return $plain;
    }
}
