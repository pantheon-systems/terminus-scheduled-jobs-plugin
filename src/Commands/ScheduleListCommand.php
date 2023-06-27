<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;

class ScheduleListCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Displays the list of job schedules accessible to the currently logged-in user.
     *
     * @authorize
     * @filter-output
     *
     * @command scheduledjobs:schedule:list
     *
     * @field-labels
     *     id: ID
     *     name: Name
     *     schedule: Schedule
     *     command: Command
     *     status: Status
     *     created_at: Created At (UTC)
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @return RowsOfFields
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function list(string $site_env)
    {
        $env = $this->getEnv($site_env);
        try {
            $rawSchedules = $this->getClient()->listSchedule($env->getSite()->id, $env->id);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error listing scheduled jobs: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
        $schedules = [];
        foreach ($rawSchedules as $rs) {
            $schedules[] = (array)$rs;
        }
        return new RowsOfFields($schedules);
    }
}
