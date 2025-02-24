<?php

/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Taskrouter
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */


namespace Twilio\Rest\Taskrouter\V1\Workspace\TaskQueue;

use Twilio\Exceptions\TwilioException;
use Twilio\InstanceResource;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;


/**
 * @property string|null $accountSid
 * @property array[]|null $activityStatistics
 * @property int $longestTaskWaitingAge
 * @property string|null $longestTaskWaitingSid
 * @property int $longestRelativeTaskAgeInQueue
 * @property string|null $longestRelativeTaskSidInQueue
 * @property string|null $taskQueueSid
 * @property array|null $tasksByPriority
 * @property array|null $tasksByStatus
 * @property int $totalAvailableWorkers
 * @property int $totalEligibleWorkers
 * @property int $totalTasks
 * @property string|null $workspaceSid
 * @property string|null $url
 */
class TaskQueueRealTimeStatisticsInstance extends InstanceResource
{
    /**
     * Initialize the TaskQueueRealTimeStatisticsInstance
     *
     * @param Version $version Version that contains the resource
     * @param mixed[] $payload The response payload
     * @param string $workspaceSid The SID of the Workspace with the TaskQueue to fetch.
     * @param string $taskQueueSid The SID of the TaskQueue for which to fetch statistics.
     */
    public function __construct(Version $version, array $payload, string $workspaceSid, string $taskQueueSid)
    {
        parent::__construct($version);

        // Marshaled Properties
        $this->properties = [
            'accountSid' => Values::array_get($payload, 'account_sid'),
            'activityStatistics' => Values::array_get($payload, 'activity_statistics'),
            'longestTaskWaitingAge' => Values::array_get($payload, 'longest_task_waiting_age'),
            'longestTaskWaitingSid' => Values::array_get($payload, 'longest_task_waiting_sid'),
            'longestRelativeTaskAgeInQueue' => Values::array_get($payload, 'longest_relative_task_age_in_queue'),
            'longestRelativeTaskSidInQueue' => Values::array_get($payload, 'longest_relative_task_sid_in_queue'),
            'taskQueueSid' => Values::array_get($payload, 'task_queue_sid'),
            'tasksByPriority' => Values::array_get($payload, 'tasks_by_priority'),
            'tasksByStatus' => Values::array_get($payload, 'tasks_by_status'),
            'totalAvailableWorkers' => Values::array_get($payload, 'total_available_workers'),
            'totalEligibleWorkers' => Values::array_get($payload, 'total_eligible_workers'),
            'totalTasks' => Values::array_get($payload, 'total_tasks'),
            'workspaceSid' => Values::array_get($payload, 'workspace_sid'),
            'url' => Values::array_get($payload, 'url'),
        ];

        $this->solution = ['workspaceSid' => $workspaceSid, 'taskQueueSid' => $taskQueueSid, ];
    }

    /**
     * Generate an instance context for the instance, the context is capable of
     * performing various actions.  All instance actions are proxied to the context
     *
     * @return TaskQueueRealTimeStatisticsContext Context for this TaskQueueRealTimeStatisticsInstance
     */
    protected function proxy(): TaskQueueRealTimeStatisticsContext
    {
        if (!$this->context) {
            $this->context = new TaskQueueRealTimeStatisticsContext(
                $this->version,
                $this->solution['workspaceSid'],
                $this->solution['taskQueueSid']
            );
        }

        return $this->context;
    }

    /**
     * Fetch the TaskQueueRealTimeStatisticsInstance
     *
     * @param array|Options $options Optional Arguments
     * @return TaskQueueRealTimeStatisticsInstance Fetched TaskQueueRealTimeStatisticsInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch(array $options = []): TaskQueueRealTimeStatisticsInstance
    {

        return $this->proxy()->fetch($options);
    }

    /**
     * Magic getter to access properties
     *
     * @param string $name Property to access
     * @return mixed The requested property
     * @throws TwilioException For unknown properties
     */
    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        if (\property_exists($this, '_' . $name)) {
            $method = 'get' . \ucfirst($name);
            return $this->$method();
        }

        throw new TwilioException('Unknown property: ' . $name);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        $context = [];
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Taskrouter.V1.TaskQueueRealTimeStatisticsInstance ' . \implode(' ', $context) . ']';
    }
}

