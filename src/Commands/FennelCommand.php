<?php

namespace nickwelsh\Fennel\Commands;

use Illuminate\Console\Command;

class FennelCommand extends Command
{
    public $signature = 'fennel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
