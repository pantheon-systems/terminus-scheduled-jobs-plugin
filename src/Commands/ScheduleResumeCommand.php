<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ScheduleResumeCommand.
 */
class ScheduleResumeCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Resume resumes a scheduled job.
     *
     * @command scheduledjobs:schedule:resume
     * @authorize
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $id The id of the scheduled job
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function resume(string $site_env, string $id): void
    {
        $env = $this->getEnv($site_env);
        try {
            $this->getClient()->resumeSchedule($env->getSite()->id, $env->id, $id);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error resuming scheduled job: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
        $this->log()->success('Scheduled job successfully resumed.');
    }
}
