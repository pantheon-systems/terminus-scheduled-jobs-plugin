<?php

namespace Pantheon\TerminusScheduledJobs\ScheduledJobsApi;

use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\TerminusScheduledJobs\Tests\Functional\Mocks\RequestMock;

/**
 * Class ScheduledJobsClientAwareTrait.
 *
 * @package \Pantheon\TerminusScheduledJobs\ScheduledJobsApi
 */
trait ScheduledJobsClientAwareTrait
{
    use RequestAwareTrait;

    /**
     * @var \Pantheon\TerminusScheduledJobs\ScheduledJobsApi\Client
     */
    protected Client $scheduledJobsClient;

    /**
     * Return the ScheduledJobsApi object.
     *
     * @return \Pantheon\TerminusScheduledJobs\ScheduledJobsApi\Client
     */
    public function getClient(): Client
    {
        if (isset($this->scheduledJobsClient)) {
            return $this->scheduledJobsClient;
        }
        return $this->scheduledJobsClient = new Client($this->request());
    }
}
