<?php

namespace Vizzle\ServiceBundle\Process;

/**
 * Service process loop.
 */
class Loop
{
    /**
     * Run loop flag.
     *
     * @var boolean
     */
    protected $run = false;

    /**
     * Loop  timer.
     *
     * @var int
     */
    protected $timer = 250;

    /**
     * Iteration count.
     *
     * @var int
     */
    protected $iteration = 0;

    /**
     * Array of calls.
     *
     * @var array
     */
    protected $calls = [];

    /**
     * Tick position.
     *
     * @var integer
     */
    protected $tick = 0;

    /**
     * Array of exception call.
     *
     * @var array
     */
    protected $onException = [];

    /**
     * Loop constructor.
     *
     * @param int $timer loop timer.
     */
    public function __construct($timer = 250)
    {
        $this->setTimer($timer);
    }

    /**
     * Set default loop timer.
     *
     * @param null|int $timer
     *
     * @return $this
     */
    public function setTimer($timer = 250)
    {
        $this->timer = (integer)$timer > 0 ? (integer)$timer : 0;

        return $this;
    }

    /**
     * Add call to loop.
     *
     * @param callable $callable
     * @param null|int $timer
     *
     * @return $this
     */
    public function add(callable $callable, $timer = null)
    {
        $timer = $timer === null ? $this->timer : (integer)$timer;

        $this->calls[] = [
            'timer'    => (integer)$timer > 0 ? (integer)$timer : 0,
            'callable' => $callable,
        ];

        return $this;
    }

    /**
     * Clear all calls
     */
    public function clear()
    {
        $this->iteration   = 0;
        $this->tick        = 0;
        $this->calls       = [];
        $this->onException = [];
    }

    /**
     * Run loop
     */
    public function run()
    {
        $this->run = true;

        gc_enable();

        while ($this->run) {
            $this->tick();
            gc_collect_cycles();
        }

        gc_disable();
    }

    /**
     * Stop loop
     */
    public function stop()
    {
        $this->run = false;
    }

    /**
     * Loop tick
     */
    public function tick()
    {
        $timer = $this->timer;
        $this->iteration += 1;

        if ($callback = isset($this->calls[$this->tick]) ? $this->calls[$this->tick] : null) {

            try {
                $callback['callable']();
            } catch (\Exception $e) {
                foreach ($this->onException as $call) {
                    $call($e);
                }
            }

            $timer = $callback['timer'];
        }

        $this->tick = count($this->calls) === $this->tick ? 0 : $this->tick + 1;

        pcntl_signal_dispatch();

        usleep($timer * 1e3);
    }

    /**
     * On exception call.
     *
     * @param callable $callable
     */
    public function onException(callable $callable)
    {
        $this->onException[] = $callable;
    }
}