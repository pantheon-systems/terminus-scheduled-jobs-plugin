<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SchedulePauseCommand.
 */
class SchedulePauseCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Pause pauses a scheduled job.
     *
     * @command scheduledjobs:schedule:pause
     * @authorize
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $id The id of the scheduled job
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function pause(string $site_env, string $id): void
    {
        $env = $this->getEnv($site_env);
        try {
            $this->getClient()->pauseSchedule($env->getSite()->id, $env->id, $id);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error pausing scheduled job: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
        $this->log()->success('Scheduled job successfully paused.');
    }
}
