<?php
declare(strict_types=1);

/**
* ErrorHandler - php errors for cool kids
* @author Filipe Dobreira <http://github.com/filp>
* Plaintext handler for command line and logs.
* @author Pierre-Yves Landuré <https://howto.biapy.com/>
*/

namespace Flames\Errors\Handler;

use Flames\Errors\Exception\Frame;
use Flames\Errors\Util\TemplateHelper;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class PlainTextHandler extends Handler
{
    const VAR_DUMP_PREFIX = '   | ';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var callable
     */
    protected $dumper;

    /**
     * @var bool
     */
    private $addTraceToOutput = true;

    /**
     * @var bool|integer
     */
    private $addTraceFunctionArgsToOutput = false;

    /**
     * @var integer
     */
    private $traceFunctionArgsOutputLimit = 1024;

    /**
     * @var bool
     */
    private $addPreviousToOutput = true;

    /**
     * @var bool
     */
    private $loggerOnly = false;

    /**
     * Constructor.
     * @throws InvalidArgumentException     If argument is not null or a LoggerInterface
     * @param  \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct($logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * Set the output logger interface.
     * @throws InvalidArgumentException     If argument is not null or a LoggerInterface
     * @param  \Psr\Log\LoggerInterface|null $logger
     */
    public function setLogger($logger = null)
    {
        if (! (is_null($logger)
            || $logger instanceof LoggerInterface)) {
            throw new InvalidArgumentException(
                'Argument to ' . __METHOD__ .
                " must be a valid Logger Interface (aka. Monolog), " .
                get_class($logger) . ' given.'
            );
        }

        $this->logger = $logger;
    }

    /**
     * @return \Psr\Log\LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set var dumper callback function.
     *
     * @param  callable $dumper
     * @return static
     */
    public function setDumper(callable $dumper)
    {
        $this->dumper = $dumper;
        return $this;
    }

    /**
     * Add error trace to output.
     * @param  bool|null  $addTraceToOutput
     * @return bool|static
     */
    public function addTraceToOutput($addTraceToOutput = null)
    {
        if (func_num_args() == 0) {
            return $this->addTraceToOutput;
        }

        $this->addTraceToOutput = (bool) $addTraceToOutput;
        return $this;
    }

    /**
     * Add previous exceptions to output.
     * @param  bool|null $addPreviousToOutput
     * @return bool|static
     */
    public function addPreviousToOutput($addPreviousToOutput = null)
    {
        if (func_num_args() == 0) {
            return $this->addPreviousToOutput;
        }

        $this->addPreviousToOutput = (bool) $addPreviousToOutput;
        return $this;
    }

    /**
     * Add error trace function arguments to output.
     * Set to True for all frame args, or integer for the n first frame args.
     * @param  bool|integer|null $addTraceFunctionArgsToOutput
     * @return static|bool|integer
     */
    public function addTraceFunctionArgsToOutput($addTraceFunctionArgsToOutput = null)
    {
        if (func_num_args() == 0) {
            return $this->addTraceFunctionArgsToOutput;
        }

        if (! is_integer($addTraceFunctionArgsToOutput)) {
            $this->addTraceFunctionArgsToOutput = (bool) $addTraceFunctionArgsToOutput;
        } else {
            $this->addTraceFunctionArgsToOutput = $addTraceFunctionArgsToOutput;
        }
        return $this;
    }

    /**
     * Set the size limit in bytes of frame arguments var_dump output.
     * If the limit is reached, the var_dump output is discarded.
     * Prevent memory limit errors.
     * @var integer
     * @return static
     */
    public function setTraceFunctionArgsOutputLimit($traceFunctionArgsOutputLimit)
    {
        $this->traceFunctionArgsOutputLimit = (int) $traceFunctionArgsOutputLimit;
        return $this;
    }

    /**
     * Create plain text response and return it as a string
     * @return string
     */
    public function generateResponse()
    {
        $exception = $this->getException();
        $message = $this->getExceptionOutput($exception);

        if ($this->addPreviousToOutput) {
            $previous = $exception->getPrevious();
            while ($previous) {
                $message .= "\n\nCaused by\n" . $this->getExceptionOutput($previous);
                $previous = $previous->getPrevious();
            }
        }


        return $message . $this->getTraceOutput() . "\n";
    }

    /**
     * Get the size limit in bytes of frame arguments var_dump output.
     * If the limit is reached, the var_dump output is discarded.
     * Prevent memory limit errors.
     * @return integer
     */
    public function getTraceFunctionArgsOutputLimit()
    {
        return $this->traceFunctionArgsOutputLimit;
    }

    /**
     * Only output to logger.
     * @param  bool|null $loggerOnly
     * @return static|bool
     */
    public function loggerOnly($loggerOnly = null)
    {
        if (func_num_args() == 0) {
            return $this->loggerOnly;
        }

        $this->loggerOnly = (bool) $loggerOnly;
        return $this;
    }

    /**
     * Test if handler can output to stdout.
     * @return bool
     */
    private function canOutput()
    {
        return !$this->loggerOnly();
    }

    /**
     * Get the frame args var_dump.
     * @param  \Flames\Errors\Exception\Frame $frame [description]
     * @param  integer                 $line  [description]
     * @return string
     */
    private function getFrameArgsOutput(Frame $frame, $line)
    {
        if ($this->addTraceFunctionArgsToOutput() === false
            || $this->addTraceFunctionArgsToOutput() < $line) {
            return '';
        }

        // Dump the arguments:
        ob_start();
        $this->dump($frame->getArgs());
        if (ob_get_length() > $this->getTraceFunctionArgsOutputLimit()) {
            // The argument var_dump is to big.
            // Discarded to limit memory usage.
            ob_clean();
            return sprintf(
                "\n%sArguments dump length greater than %d Bytes. Discarded.",
                self::VAR_DUMP_PREFIX,
                $this->getTraceFunctionArgsOutputLimit()
            );
        }

        return sprintf(
            "\n%s",
            preg_replace('/^/m', self::VAR_DUMP_PREFIX, ob_get_clean())
        );
    }

    /**
     * Dump variable.
     *
     * @param mixed $var
     * @return void
     */
    protected function dump($var)
    {
        if ($this->dumper) {
            call_user_func($this->dumper, $var);
        } else {
            var_dump($var);
        }
    }

    /**
     * Get the exception trace as plain text.
     * @return string
     */
    private function getTraceOutput()
    {
        if (! $this->addTraceToOutput()) {
            return '';
        }
        $inspector = $this->getInspector();
        $frames = $inspector->getFrames($this->getRun()->getFrameFilters());

        $response = "\n" . $this->ansi('Stack trace:', '33');

        $line = 1;
        $pathHelper = new TemplateHelper();
        foreach ($frames as $frame) {
            /** @var Frame $frame */
            $class = $frame->getClass();
            $fileLabel = $frame->getFile()
                ? $pathHelper->frameFileLabel($frame->getFile(), (int) $frame->getLine())
                : '<#unknown>';
            $lineSuffix = $frame->getFile()
                ? $pathHelper->frameFileLine($frame->getFile(), (int) $frame->getLine())
                : null;
            $location = $lineSuffix !== null ? ($fileLabel . ':' . $lineSuffix) : $fileLabel;

            $template = "\n  %s. %s->%s() %s%s";
            if (! $class) {
                $template = "\n  %s. %s%s() %s%s";
            }

            $response .= sprintf(
                $template,
                $this->ansi($line, '90'),
                $this->ansi($class, '36'),
                $this->ansi($frame->getFunction(), '37'),
                $this->ansi($location, '90'),
                $this->getFrameArgsOutput($frame, $line)
            );

            $line++;
        }

        return $response;
    }

    /**
     * Get the exception as plain text.
     * @param \Throwable $exception
     * @return string
     */
    private function getExceptionOutput($exception)
    {
        $pathHelper = new TemplateHelper();
        $fileLabel = $pathHelper->frameFileLabel($exception->getFile(), (int) $exception->getLine());
        $lineSuffix = $pathHelper->frameFileLine($exception->getFile(), (int) $exception->getLine());
        $location = $lineSuffix !== null ? ($fileLabel . ':' . $lineSuffix) : $fileLabel;

        return sprintf(
            "%s: %s\n  %s %s",
            $this->ansi(get_class($exception), '31;1'),
            $this->ansi($exception->getMessage(), '97;1'),
            $this->ansi('in', '90'),
            $this->ansi($location, '90')
        );
    }

    /**
     * Wrap text in an ANSI color code for terminal output.
     * @param  mixed  $text
     * @param  string $code ANSI color code
     * @return string
     */
    private function ansi($text, string $code): string
    {
        if (PHP_SAPI !== 'cli') {
            return (string) $text;
        }
        return "\033[{$code}m{$text}\033[0m";
    }

    /**
     * @return int
     */
    public function handle()
    {
        $response = $this->generateResponse();

        if ($this->getLogger()) {
            $this->getLogger()->error($response);
        }

        if (! $this->canOutput()) {
            return Handler::DONE;
        }

        echo $response;

        return Handler::QUIT;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return 'text/plain';
    }
}
