<?php

namespace Pantheon\TerminusScheduledJobs\Tests\Functional;

use Pantheon\Terminus\Tests\Functional\TerminusTestBase;

/**
 * Class CreateCommandTest.
 *
 * @package \Pantheon\TerminusScheduledJobs\Tests\Functional
 */
final class ScheduledJobsCommandsTest extends TerminusTestBase
{
    protected const JOB_NAME = 'job test ';
    protected const JOB_COMMAND = 'drush version';
    protected const JOB_SCHEDULE = '* */15 * * *';

    /**
     * @test
     * @covers \Pantheon\TerminusScheduledJobs\Commands\CreateCommand
     * @covers \Pantheon\TerminusScheduledJobs\Commands\ListCommand
     * @covers \Pantheon\TerminusScheduledJobs\Commands\DeleteCommand
     *
     * @group scheduledJobs
     * @group short
     */
    public function testScheduledJobsCommands()
    {
        $this->assertCommandExists('scheduledjobs:schedule:create');
        $this->assertCommandExists('scheduledjobs:schedule:list');
        $this->assertCommandExists('scheduledjobs:schedule:delete');
        $this->assertCommandExists('scheduledjobs:job:list');
        $this->assertCommandExists('scheduledjobs:job:logs');

        // Make job name unique as this test runs in parallel on different
        // versions of php.
        $jobName = self::JOB_NAME . uniqid($more_entropy = true);

        // Create schedule.
        $this->terminus(sprintf(
            'scheduledjobs:schedule:create %s "%s" "%s" "%s"',
            $this->getSiteEnv(),
            $jobName,
            self::JOB_COMMAND,
            self::JOB_SCHEDULE
        ));

        // List schedules.
        $schedules = $this->terminusJsonResponse(sprintf('scheduledjobs:schedule:list %s', $this->getSiteEnv()));
        $this->assertIsArray($schedules);
        $this->assertNotEmpty($schedules);

        $schedule = $this->getScheduleByName($schedules, $jobName);
        $this->assertNotNull($schedule, 'Schedule not found in list.');

        $scheduleId = $schedule['id'];
        $this->assertNotEmpty($scheduleId);

        // Pause schedule.
        $this->terminus(sprintf('scheduledjobs:schedule:pause %s %s', $this->getSiteEnv(), $scheduleId));
        
        // Make sure it's paused.
        $schedules = $this->terminusJsonResponse(sprintf('scheduledjobs:schedule:list %s', $this->getSiteEnv()));
        $this->assertIsArray($schedules);
        $this->assertNotEmpty($schedules);
        $schedule = $this->getScheduleByName($schedules, $jobName);
        $this->assertNotNull($schedule, 'Schedule not found in list.');
        $this->assertEquals($schedule['status'], 'PAUSED');

        // Resume schedule.
        $this->terminus(sprintf('scheduledjobs:schedule:resume %s %s', $this->getSiteEnv(), $scheduleId));

        // Make sure it's enabled.
        $schedules = $this->terminusJsonResponse(sprintf('scheduledjobs:schedule:list %s', $this->getSiteEnv()));
        $this->assertIsArray($schedules);
        $this->assertNotEmpty($schedules);
        $schedule = $this->getScheduleByName($schedules, $jobName);
        $this->assertNotNull($schedule, 'Schedule not found in list.');
        $this->assertEquals($schedule['status'], 'ENABLED');

        // Check the budget.
        $budgetInfos = $this->terminusJsonResponse(sprintf('scheduledjobs:budget:info %s', $this->getSiteEnv()));
        $this->assertIsArray($budgetInfos);
        $this->assertNotEmpty($budgetInfos);
        $budgetInfo = (array)$budgetInfos[0];
        $this->assertNotEmpty($budgetInfo['elapsed_budget'], '0m');

        // Delete schedule.
        $this->terminus(sprintf('scheduledjobs:schedule:delete %s %s', $this->getSiteEnv(), $scheduleId));

        // List schedules again.
        $schedules = $this->terminusJsonResponse(sprintf('scheduledjobs:schedule:list %s', $this->getSiteEnv()));
        $this->assertIsArray($schedules);

        $schedule = $this->getScheduleByName($schedules, $jobName);
        $this->assertNull($schedule, 'Schedule not found in list.');
    }

    private function getScheduleByName(array $schedules, string $jobName)
    {
        foreach ($schedules as $s) {
            if ($s['name'] == $jobName) {
                return $s;
            }
        }
        return null;
    }
}
