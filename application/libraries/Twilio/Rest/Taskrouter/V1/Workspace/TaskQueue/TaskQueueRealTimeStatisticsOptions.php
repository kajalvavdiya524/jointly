<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Taskrouter\V1\Workspace\TaskQueue;

use Twilio\Options;
use Twilio\Values;

abstract class TaskQueueRealTimeStatisticsOptions {
    /**
     * @param string $taskChannel The task_channel
     * @return FetchTaskQueueRealTimeStatisticsOptions Options builder
     */
    public static function fetch($taskChannel = Values::NONE) {
        return new FetchTaskQueueRealTimeStatisticsOptions($taskChannel);
    }
}

class FetchTaskQueueRealTimeStatisticsOptions extends Options {
    /**
     * @param string $taskChannel The task_channel
     */
    public function __construct($taskChannel = Values::NONE) {
        $this->options['taskChannel'] = $taskChannel;
    }

    /**
     * The task_channel
     * 
     * @param string $taskChannel The task_channel
     * @return $this Fluent Builder
     */
    public function setTaskChannel($taskChannel) {
        $this->options['taskChannel'] = $taskChannel;
        return $this;
    }

    /**
     * Provide a friendly representation
     * 
     * @return string Machine friendly representation
     */
    public function __toString() {
        $options = array();
        foreach ($this->options as $key => $value) {
            if ($value != Values::NONE) {
                $options[] = "$key=$value";
            }
        }
        return '[Twilio.Taskrouter.V1.FetchTaskQueueRealTimeStatisticsOptions ' . implode(' ', $options) . ']';
    }
}