<?php

namespace Imagewize\SageNativeBlock\Console;

use Roots\Acorn\Console\Commands\Command;
use Imagewize\SageNativeBlock\Facades\Example;

class SageNativeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-native-block';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'My custom Acorn command.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info(
            Example::getQuote()
        );
    }
}
