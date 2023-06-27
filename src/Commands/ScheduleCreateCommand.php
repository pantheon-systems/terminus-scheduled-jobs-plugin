<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ScheduleCreateCommand.
 */
class ScheduleCreateCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Create creates a scheduled job based on the received spec.
     *
     * @command scheduledjobs:schedule:create
     * @authorize
     *
     * @param string $site_env Site & environment in the format `site-name.env`.
     * @param string $name A human readable name for this scheduled job.
     * @param string $cmd The command to run.
     * @param string $schedule The schedule to run the command. This must be in a valid cron format.
     *
     * @usage <site_env> "<name>" "<cmd>" "<schedule>" Create a scheduled job <name> to run <cmd> on <schedule>.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create(string $site_env, string $name, string $cmd, string $schedule): void {
        $env = $this->getEnv($site_env);
        try {
            $this->getClient()->createSchedule($env->getSite()->id, $env->id, $name, $cmd, $schedule);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error creating scheduled job: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
        $this->log()->success('Scheduled job successfully created.');
    }
}
