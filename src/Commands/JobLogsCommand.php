<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

class JobLogsCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Displays the logs associated with the given job id.
     *
     * @authorize
     * @filter-output
     *
     * @command scheduledjobs:job:logs
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $job_id The job id for which to show logs.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function logs(string $site_env, $job_id)
    {
        $env = $this->getEnv($site_env);
        try {
            $job = $this->getClient()->getJob($env->getSite()->id, $env->id, $job_id);
            if (empty($job) || empty($job["external_id"])) {
                throw new TerminusException(
                    'Error retrieving logs: job not found or logs not available.'
                );
            }
            $logs = $this->getClient()->getLogs($env->getSite()->id, $env->id, $job["external_id"]);
            // Dumping raw logs without formatting.
            if (is_array($logs) && !empty($logs[0])) {
                print($logs[0]);
            }
        } catch (TerminusNotFoundException $t) {
            $this->log()->notice('Job not found or logs not available.');
            return;
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error retrieving logs: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
    }
}
