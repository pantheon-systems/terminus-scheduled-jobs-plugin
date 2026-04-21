<?php

namespace Pantheon\TerminusScheduledJobs\Tests\Unit;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Request\RequestOperationResult;
use Pantheon\Terminus\Session\Session;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private const SITE_ID = 'test-site-id';
    private const ENV = 'dev';
    private const BASE_URL = 'https://example.com/api';
    private const TOKEN = 'test-token';

    private $request;
    private Client $client;

    protected function setUp(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('session')->willReturn(self::TOKEN);

        $this->request = $this->createMock(Request::class);
        $this->request->method('session')->willReturn($session);

        // Anonymous subclass overrides getPantheonApiBaseUri() so tests don't
        // need to wire up the full terminus config chain.
        $this->client = new class($this->request) extends Client {
            protected function getPantheonApiBaseUri(): string
            {
                return 'https://example.com/api';
            }
        };
    }

    private function makeResult(int $statusCode, $data, string $reason = 'OK'): RequestOperationResult
    {
        return new RequestOperationResult([
            'data' => $data,
            'headers' => [],
            'status_code' => $statusCode,
            'status_code_reason' => $reason,
        ]);
    }

    // -------------------------------------------------------------------------
    // Endpoint / method assertions
    // -------------------------------------------------------------------------

    public function testCreateScheduleCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs',
                $this->callback(fn($opts) =>
                    $opts['method'] === 'PUT' &&
                    $opts['json']['name'] === 'my-job' &&
                    $opts['json']['command'] === 'drush cr' &&
                    $opts['json']['schedule'] === '0 * * * *'
                )
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->createSchedule(self::SITE_ID, self::ENV, 'my-job', 'drush cr', '0 * * * *');
    }

    public function testDeleteScheduleCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs/sched-id',
                $this->callback(fn($opts) => $opts['method'] === 'DELETE')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->deleteSchedule(self::SITE_ID, self::ENV, 'sched-id');
    }

    public function testPauseScheduleCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs/sched-id/pause',
                $this->callback(fn($opts) => $opts['method'] === 'POST')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->pauseSchedule(self::SITE_ID, self::ENV, 'sched-id');
    }

    public function testResumeScheduleCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs/sched-id/resume',
                $this->callback(fn($opts) => $opts['method'] === 'POST')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->resumeSchedule(self::SITE_ID, self::ENV, 'sched-id');
    }

    public function testListScheduleCallsCorrectEndpoint(): void
    {
        $data = [['id' => 'sched-1', 'name' => 'my-job']];

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs',
                $this->callback(fn($opts) => $opts['method'] === 'GET')
            )
            ->willReturn($this->makeResult(200, $data));

        $result = $this->client->listSchedule(self::SITE_ID, self::ENV);
        $this->assertEquals($data, $result);
    }

    public function testListJobsCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs/sched-id/jobs',
                $this->callback(fn($opts) => $opts['method'] === 'GET')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->listJobs(self::SITE_ID, self::ENV, 'sched-id');
    }

    public function testGetJobCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/cronjobs/jobs/job-id',
                $this->callback(fn($opts) => $opts['method'] === 'GET')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->getJob(self::SITE_ID, self::ENV, 'job-id');
    }

    public function testGetLogsCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/' . self::ENV . '/build/logs-v2/job-id?logs_format=raw',
                $this->callback(fn($opts) => $opts['method'] === 'GET')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->getLogs(self::SITE_ID, self::ENV, 'job-id');
    }

    public function testBudgetInfoCallsCorrectEndpoint(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                self::BASE_URL . '/sites/' . self::SITE_ID . '/environments/dev/cronjobs/budgets',
                $this->callback(fn($opts) => $opts['method'] === 'GET')
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->budgetInfo(self::SITE_ID);
    }

    // -------------------------------------------------------------------------
    // Auth header
    // -------------------------------------------------------------------------

    public function testRequestIncludesAuthHeader(): void
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->anything(),
                $this->callback(fn($opts) =>
                    isset($opts['headers']['Authorization']) &&
                    $opts['headers']['Authorization'] === 'Bearer ' . self::TOKEN
                )
            )
            ->willReturn($this->makeResult(200, []));

        $this->client->listSchedule(self::SITE_ID, self::ENV);
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    public function testThrowsNotFoundExceptionOn404(): void
    {
        $this->request->method('request')
            ->willReturn($this->makeResult(404, null, 'Not Found'));

        $this->expectException(TerminusNotFoundException::class);
        $this->client->listSchedule(self::SITE_ID, self::ENV);
    }

    public function testThrowsTerminusExceptionWithApiErrorMessage(): void
    {
        $errorData = (object)['error' => 'Schedule not found'];
        $this->request->method('request')
            ->willReturn($this->makeResult(400, $errorData, 'Bad Request'));

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('Schedule not found');
        $this->client->listSchedule(self::SITE_ID, self::ENV);
    }

    public function testThrowsTerminusExceptionOnUnexpectedError(): void
    {
        $this->request->method('request')
            ->willReturn($this->makeResult(500, null, 'Internal Server Error'));

        $this->expectException(TerminusException::class);
        $this->client->listSchedule(self::SITE_ID, self::ENV);
    }
}
