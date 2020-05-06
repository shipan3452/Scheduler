<?php

namespace Scheduler;

use Closure;
use Cron\CronExpression;
use Scheduler\Mutex\EventMutex;
use Symfony\Component\Process\Process;
use Scheduler\Utility\ManagesFrequencies;
use Scheduler\Utility\CommandBuilder;

class Event
{
    use ManagesFrequencies;
    /**
     * The command string.
     *
     * @var string
     */
    public $command;

    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    public $expression = '* * * * *';

    /**
     * The user the command should run as.
     *
     * @var string
     */
    public $user;


    /**
     * Indicates if the command should not overlap itself.
     *
     * @var bool
     */
    public $withoutOverlapping = false;


    /**
     * The amount of time the mutex should be valid.
     *
     * @var int
     */
    public $expiresAt = 1440;

    /**
     * Indicates if the command should run in background.
     *
     * @var bool
     */
    public $runInBackground = false;



    /**
     * The location that output should be sent to.
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * Indicates whether output should be appended.
     *
     * @var bool
     */
    public $shouldAppendOutput = false;

    /**
     * The array of callbacks to be run before the event is started.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the event is finished.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * The human readable description of the event.
     *
     * @var string
     */
    public $description;

    /**
     * The event mutex implementation.
     *
     * @var EventMutex
     */
    public $mutex;

    /**
     * Create a new event instance.
     *
     * @param  EventMutex  $mutex
     * @param  string  $command
     * @return void
     */
    public function __construct($command, EventMutex $mutex = null)
    {
        $this->mutex = $mutex;
        $this->command = $command;
        $this->output = $this->getDefaultOutput();
    }

    /**
     * Get the default output depending on the OS.
     *
     * @return string
     */
    public function getDefaultOutput()
    {
        return (DIRECTORY_SEPARATOR == '\\') ? 'NUL' : '/dev/null';
    }

    /**
     * Run the given event.
     *
     * @return void
     */
    public function run()
    {
        $this->runInBackground
        ? $this->runCommandInBackground()
        : $this->runCommandInForeground();
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
        return 'Scheduler' . DIRECTORY_SEPARATOR . 'event-' . sha1($this->expression . $this->command);
    }

    /**
     * Run the command in the foreground.
     *
     * @return void
     */
    protected function runCommandInForeground()
    {
        $this->callBeforeCallbacks();

        (new Process(
            $this->buildCommand(),
            null,
            null,
            null,
            null
        ))->run();

        $this->callAfterCallbacks();
    }

    /**
     * Run the command in the background.
     *
     * @return void
     */
    protected function runCommandInBackground()
    {
        $this->callBeforeCallbacks();

        (new Process(
            $this->buildCommand(),
            null,
            null,
            null,
            null
        ))->run();
    }

    /**
     * Call all of the "before" callbacks for the event.
     *
     * @return void
     */
    public function callBeforeCallbacks()
    {
        foreach ($this->beforeCallbacks as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Call all of the "after" callbacks for the event.
     * @return void
     */
    public function callAfterCallbacks()
    {
        foreach ($this->afterCallbacks as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function buildCommand()
    {
        return (new CommandBuilder)->buildCommand($this);
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @return bool
     */
    public function isDue()
    {
        return $this->expressionPasses() ;
    }

 

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        return CronExpression::factory($this->expression)->isDue('now');
    }


    /**
     * Send the output of the command to a given location.
     *
     * @param  string  $location
     * @param  bool  $append
     * @return $this
     */
    public function sendOutputTo($location, $append = false)
    {
        $this->output = $location;

        $this->shouldAppendOutput = $append;

        return $this;
    }

    /**
     * Append the output of the command to a given location.
     *
     * @param  string  $location
     * @return $this
     */
    public function appendOutputTo($location)
    {
        return $this->sendOutputTo($location, true);
    }

    /**
     * State that the command should run in background.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * Set which user the command should run as.
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }


    /**
     * Do not allow the event to overlap each other.
     *
     * @param  int  $expiresAt
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->then(function () {
            $this->mutex->forget($this);
        });
    }


    /**
     * Register a callback to be called before the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        return $this->then($callback);
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function name($description)
    {
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return $this->buildCommand();
    }

    /**
     * Get the Cron expression for the event.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Set the event mutex implementation to be used.
     *
     * @param  EventMutex  $mutex
     * @return $this
     */
    public function preventOverlapsUsing(EventMutex $mutex)
    {
        $this->mutex = $mutex;

        return $this;
    }
}
