<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

class JobListCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Displays the list of jobs associated with a given schedule accessible to the currently logged-in user.
     *
     * @authorize
     * @filter-output
     *
     * @command scheduledjobs:job:list
     *
     * @field-labels
     *     id: ID
     *     start_time: Start Time
     *     end_time: End Time
     *     status: Status
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $schedule_id The schedule id for which to list jobs.
     * @return RowsOfFields
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function list(string $site_env, $schedule_id)
    {
        $env = $this->getEnv($site_env);
        try {
            $jobs = $this->getClient()->listJobs($env->getSite()->id, $env->id, $schedule_id);
        } catch (TerminusNotFoundException $t) {
            $this->log()->notice('No jobs found for the given schedule.');
            return new RowsOfFields([]);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error listing scheduled jobs: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
        $jobs_list = [];
        foreach ($jobs as $job) {
            $jobs_list[] = (array)$job;
        }
        return new RowsOfFields($jobs_list);
    }
}
