<?php

declare(strict_types=1);

namespace Pokio\Support;

use Pokio\Promise;

final readonly class JoinHandle
{
    public function __construct(
        private Promise $promise
    ) {
        //
    }

    /**
     * Awaits the resolution of the task.
     */
    public function await(): mixed
    {
        return await($this->promise);
    }
    
    /**
     * Gets the raw promise, if needed.
     */
    public function promise(): Promise
    {
        return $this->promise;
    }
}
