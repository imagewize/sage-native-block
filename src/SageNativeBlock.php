<?php

namespace Imagewize\SageNativeBlock;

use Illuminate\Support\Arr;
use Roots\Acorn\Application;

class SageNativeBlock
{
    /**
     * The application instance.
     *
     * @var \Roots\Acorn\Application
     */
    protected $app;

    /**
     * Create a new Example instance.
     *
     * @param  \Roots\Acorn\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Retrieve a random inspirational quote.
     *
     * @return string
     */
    public function getQuote()
    {
        return Arr::random(
            config('sage-native-block.quotes')
        );
    }
}
