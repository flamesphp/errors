<?php
/**
 * ErrorHandler - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Flames\Errors\Inspector;

/**
 * @internal
 */
interface InspectorFactoryInterface
{
    /**
     * @param \Throwable $exception
     * @return InspectorInterface
     */
    public function create($exception);
}
