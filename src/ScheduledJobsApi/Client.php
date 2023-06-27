<?php

namespace Pantheon\TerminusScheduledJobs\ScheduledJobsApi;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\Request;

/**
 * ScheduledJobs API Client.
 */
class Client
{
    /**
     * @var \Pantheon\Terminus\Request\Request
     */
    protected Request $request;

    /**
     * Constructor.
     *
     * @param \Pantheon\Terminus\Request\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Creates a job schedule.
     *
     * @param string $site_id
     * @param string $env
     * @param string $name
     * @param string $command
     * @param string $schedule
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function createSchedule(string $site_id, string $env, string $name, string $command, string $schedule)
    {
        $request_options = [
            'json' => [
                'name' => $name,
                'command' => $command,
                'schedule' => $schedule,
            ],
            'method' => 'PUT',
        ];

        $this->requestApi(
            sprintf('sites/%s/environments/%s/cronjobs', $site_id, $env),
            $request_options
        );
    }

    /**
     * Deletes a job schedule.
     *
     * @param string $site_id
     * @param string $env
     * @param string $schedule_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function deleteSchedule(string $site_id, string $env, string $schedule_id)
    {
        $request_options = ['method' => 'DELETE'];
        $this->requestApi(
            sprintf('sites/%s/environments/%s/cronjobs/%s', $site_id, $env, $schedule_id),
            $request_options
        );
    }

    /**
     * Pauses a job schedule.
     *
     * @param string $site_id
     * @param string $env
     * @param string $schedule_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function pauseSchedule(string $site_id, string $env, string $schedule_id)
    {
        $request_options = ['method' => 'POST'];
        return $this->requestApi(sprintf('sites/%s/environments/%s/cronjobs/%s/pause', $site_id, $env, $schedule_id), $request_options);
    }

    /**
     * Resumes a job schedule.
     *
     * @param string $site_id
     * @param string $env
     * @param string $schedule_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function resumeSchedule(string $site_id, string $env, string $schedule_id)
    {
        $request_options = ['method' => 'POST'];
        return $this->requestApi(sprintf('sites/%s/environments/%s/cronjobs/%s/resume', $site_id, $env, $schedule_id), $request_options);
    }
    
    /**
     * Lists job schedules.
     *
     * @param string $site_id
     * @param string $env
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listSchedule(string $site_id, string $env)
    {
        $request_options = ['method' => 'GET'];
        return $this->requestApi(sprintf('sites/%s/environments/%s/cronjobs', $site_id, $env), $request_options);
    }

    /**
     * Lists jobs associated with a given schedule.
     *
     * @param string $site_id
     * @param string $env
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listJobs(string $site_id, string $env, string $schedule_id)
    {
        $request_options = ['method' => 'GET'];
        return $this->requestApi(sprintf('sites/%s/environments/%s/cronjobs/%s/jobs', $site_id, $env, $schedule_id), $request_options);
    }

    /**
     * Returns the job with the given id.
     *
     * @param string $site_id
     * @param string $env
     * @param string $job_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function getJob(string $site_id, string $env, string $job_id)
    {
        $request_options = ['method' => 'GET'];
        return $this->requestApi(sprintf('sites/%s/environments/%s/cronjobs/jobs/%s', $site_id, $env, $job_id), $request_options);
    }

    /**
     * Gets the logs associated with the given job id.
     *
     * @param string $site_id
     * @param string $env
     * @param string $job_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function getLogs(string $site_id, string $env, string $job_id)
    {
        $request_options = ['method' => 'GET'];
        $path = sprintf('sites/%s/environments/%s/build/logs-v2/%s?logs_format=raw', $site_id, $env, $job_id);
        return $this->requestApi($path, $request_options);
    }

    /**
     * Show budget info for a site.
     *
     * @param string $site_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function budgetInfo(string $site_id)
    {
        $request_options = ['method' => 'GET'];
        // env doesn't matter here, so just use dev.
        return $this->requestApi(sprintf('sites/%s/environments/dev/cronjobs/budgets', $site_id), $request_options);
    }

    /**
     * Performs the request to API path.
     *
     * @param string $path
     * @param array $options
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function requestApi(string $path, array $options = []): array
    {
        $url = sprintf('%s/%s', $this->getPantheonApiBaseUri(), $path);
        $options = array_merge(
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => sprintf('Bearer %s', $this->request->session()->get('session')),
                ],
                // Do not convert http errors to exceptions
                'http_errors' => false,
            ],
            $options
        );

        $result = $this->request->request($url, $options);
        $statusCode = $result->getStatusCode();
        $data = $result->getData();
        // If it went ok, just return data.
        if ($statusCode >= 200 && $statusCode < 300) {
            return (array) $result->getData();
        } elseif ($statusCode == 404) {
            throw new TerminusNotFoundException();
        } elseif (!empty($data->error)) {
            // If error was correctly set from backend, throw it.
            throw new TerminusException($data->error);
        }
        throw new TerminusException(sprintf('An error ocurred. Code: %s. Message: %s', $statusCode, $result->getStatusCodeReason()));
    }

    /**
     * Returns Pantheon API base URI.
     *
     * @return string
     */
    protected function getPantheonApiBaseUri(): string
    {
        $config = $this->request->getConfig();
        return sprintf('%s://%s:%s/api', $config->get('protocol'), $config->get('host'), $config->get('port'));
    }
}
