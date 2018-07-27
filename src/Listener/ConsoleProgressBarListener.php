<?php

namespace mate\Monitor\Listener;

/**
 * @package mateTest\Monitor\Listener
 */
class ConsoleProgressBarListener extends AbstractProgressListener
{

    const HIGHLIGHT_NEUTRAL = "\033[0;30m\033[47m%s\033[0m";
    const HIGHLIGHT_SUCCESS = "\033[0;30m\033[42m%s\033[0m";

    const PARAM_LAST_TRIGGER = "lastTrigger";
    const DEFAULT_TASK_NAME = "task";

    /**
     * Print start application message
     *
     * @param ProgressEvent $event
     */
    public function startApplication(ProgressEvent $event)
    {
        echo sprintf(
                self::HIGHLIGHT_NEUTRAL,
                "Starting the application"
            ) . "\n\n";
    }

    /**
     * Print start task message
     *
     * @param ProgressEvent $event
     */
    public function start(ProgressEvent $event)
    {
        $taskName = $event->getTaskName();
        $taskName = $taskName ? $taskName : self::DEFAULT_TASK_NAME;
        $totalExecutions = $event->getTotalExecutions();

        echo "Start $taskName with $totalExecutions executions\n";
    }

    /**
     * Print progress bar and refresh once a second
     *
     * @param ProgressEvent $event
     */
    public function execute(ProgressEvent $event)
    {
        $currentTime = time();
        $lastTrigger = $event->getParam(self::PARAM_LAST_TRIGGER);

        $total = $event->getTotalExecutions();
        $done = $event->getDoneCount();

        if($lastTrigger !== $currentTime || $total <= $done) {
            $this->showProgress($event);
            $event->setParam(self::PARAM_LAST_TRIGGER, $currentTime);
        }
    }

    /**
     * Print task execution time
     *
     * @param ProgressEvent $event
     */
    public function finish(ProgressEvent $event)
    {
        $time = time() - $event->getStart();
        $total = gmdate("H:i:s", $time);
        echo "Total execution time: $total\n";

        $skipped = $event->getSkippedCount();
        if($skipped > 0) {
            $done = $event->getDoneCount();
            $totalCount = $done + $skipped;
            echo "Skipped $skipped/$totalCount executions\n";
        }
        echo "\n";
    }

    /**
     * Print success message
     *
     * @param ProgressEvent $event
     */
    public function finishApplication(ProgressEvent $event)
    {
        $time = time() - $event->getAppStart();
        $total = gmdate("H:i:s", $time);

        echo sprintf(self::HIGHLIGHT_SUCCESS, "All tasks finished") . "\n";
        echo sprintf(self::HIGHLIGHT_SUCCESS, "Total execution time: $total") . "\n";
    }

    /**
     * show a status bar in the console
     *
     * @param ProgressEvent $event
     * @param int $size optional size of the status bar
     * @return void
     *
     */
    protected function showProgress(ProgressEvent $event, $size = 30)
    {
        $done = $event->getDoneCount();
        $total = $event->getTotalExecutions();
        $startTime = $event->getStart();

        // if we go over our bound, just ignore it
        if($done > $total || $done == 0) return;

        $now = time();

        $perc = (double)($done / $total);

        $bar = floor($perc * $size);

        $statusBar = "\r[";
        $statusBar .= str_repeat("=", $bar);
        if($bar < $size) {
            $statusBar .= ">";
            $statusBar .= str_repeat(" ", $size - $bar);
        } else {
            $statusBar .= "=";
        }

        $disp = number_format($perc * 100, 0);

        $statusBar .= "] $disp%  $done/$total";

        $rate = ($now - $startTime) / $done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);

        $elapsed = $now - $startTime;

        $statusBar .= " Remaining: " . gmdate("H:i:s", $eta) . "  Elapsed: " . gmdate("H:i:s", $elapsed) . "";

        echo "$statusBar  ";

        flush();

        // when done, send a newline
        if($done == $total) {
            echo "\n";
        }

    }

}