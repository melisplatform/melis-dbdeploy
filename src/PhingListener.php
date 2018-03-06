<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisDbDeploy;

use \BuildListener;
use \BuildEvent;

class PhingListener implements BuildListener
{
    public function buildStarted(BuildEvent $event)
    {
        $this->stdout($event->getMessage());
    }

    public function targetStarted(BuildEvent $event)
    {
        // TODO: Implement targetStarted() method.
    }

    public function taskStarted(BuildEvent $event)
    {
        // TODO: Implement taskStarted() method.
    }

    public function taskFinished(BuildEvent $event)
    {
        // TODO: Implement taskFinished() method.
    }

    public function targetFinished(BuildEvent $event)
    {
        // TODO: Implement targetFinished() method.
    }

    public function buildFinished(BuildEvent $event)
    {
        $this->stdout($event->getMessage());
    }

    public function messageLogged(BuildEvent $event)
    {
        $enableLogging = false;
        if($enableLogging)
            $this->stdout($event->getMessage());
    }

    /**
     * @param string $output
     */
    private function stdout($output)
    {
        $stream = \Phing::getOutputStream();
        $stream->write($output . "\n");
    }
}