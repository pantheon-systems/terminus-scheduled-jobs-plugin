# Terminus Scheduled Jobs Plugin

## Scheduled Jobs

This plugin allows scheduling jobs on the Pantheon platform. Due to the private beta status, the feature is only enabled upon request on a site by site basis. Each job runs in its own container with access to both the database and distributed file system. For now, other features such as access to the New Relic agent or secure integration are not available.

### Installation

The only requirement is terminus itself, version 3.x. To install the plugin, the current repository needs to be cloned locally, followed by the execution of the following command:

```bash
terminus self:plugin:install $PATH_TO_LOCAL_CLONE
```

### Job Schedules

Job schedules are characterized as job definitions which allow setting a name for each job, a command and schedule in the UNIX cron format (https://en.wikipedia.org/wiki/Cron).

```bash
# ┌───────────── minute (0 - 59)
# │ ┌───────────── hour (0 - 23)
# │ │ ┌───────────── day of the month (1 - 31)
# │ │ │ ┌───────────── month (1 - 12)
# │ │ │ │ ┌───────────── day of the week (0 - 6) (Sunday to Saturday;
# │ │ │ │ │                                   7 is also Sunday on some systems)
# │ │ │ │ │
# │ │ │ │ │
# * * * * * <command to execute>
```

#### Creating a new schedule

Creating a new schedule can be performed running the command below. The given example runs an hourly job executing Drupal cron:

```bash
terminus scheduledjobs:create $SITE_NAME.$ENVIRONMENT "test-scheduled-job-hourly" "drush -vvv cron" "0 * * * *"
```

#### Listing schedules

```bash
terminus scheduledjobs:schedule:list $SITE_NAME.$ENVIRONMENT

-------------------------------------- --------------------------- ------------- ---------------------------------- --------- ----------------------
 ID                                     Name                        Schedule      Command                            Status    Created At (UTC)
-------------------------------------- --------------------------- ------------- ---------------------------------- --------- ----------------------
 d178dd16-b0e3-47dc-a446-1bf4343f7fff   test-scheduled-job-hourly   0 * * * *     ls -la /files && drush -vvv cron   ENABLED   2023-05-19T07:34:26Z
-------------------------------------- --------------------------- ------------- ---------------------------------- --------- ----------------------
```

#### Pausing / resuming schedules

At any point, job executions can either be paused or resumed.

```bash
terminus scheduledjobs:schedule:pause $SITE_NAME.$ENVIRONMENT $SCHEDULE_ID
terminus scheduledjobs:schedule:resume $SITE_NAME.$ENVIRONMENT $SCHEDULE_ID
```

#### Deleting schedules

```bash
terminus scheduledjobs:schedule:delete $SITE_NAME.$ENVIRONMENT $SCHEDULE_ID
```

### Jobs

Jobs are defined as individual executions associated with a certain schedule. Listing jobs involves obtaining the schedule id.

```bash
terminus scheduledjobs:job:list $SITE_NAME.$ENVIRONMENT $SCHEDULE_ID

-------------------------------------- ------------------------------- ------------------------------- ---------
 ID                                     Start Time                      End Time                        Status
-------------------------------------- ------------------------------- ------------------------------- ---------
 ca93e729-58e8-489f-805b-f73d4102c5c0   2023-05-26 07:00:03 +0000 UTC   2023-05-26 07:01:57 +0000 UTC   SUCCESS
 808a3a84-b1c6-42cf-92c8-f0b6afe959c8   2023-05-26 06:00:03 +0000 UTC   2023-05-26 06:01:51 +0000 UTC   SUCCESS
 e0a74a62-6705-4f0c-830c-0190f57dc1c0   2023-05-26 05:00:00 +0000 UTC   2023-05-26 05:01:34 +0000 UTC   SUCCESS
 7effda3b-be2e-42b9-9093-cd373b7b3079   2023-05-26 04:00:00 +0000 UTC   2023-05-26 04:01:33 +0000 UTC   SUCCESS
 54c39e63-fcf5-41f0-81ec-4163fc1f498b   2023-05-26 03:00:00 +0000 UTC   2023-05-26 03:01:54 +0000 UTC   SUCCESS
 fdf2cf55-88f5-44a1-8a7e-d422291f625a   2023-05-26 02:00:02 +0000 UTC   2023-05-26 02:01:51 +0000 UTC   SUCCESS
-------------------------------------- ------------------------------- ------------------------------- ---------
```

### Logs

Viewing logs associated with jobs is possible by passing the job ID to the command below:

```bash
terminus scheduledjobs:job:logs $SITE_NAME.$ENVIRONMENT $JOB_ID
```

## Quotas and further considerations

The private beta nature of this feature comes with a quota defined at the site level as a daily runtime budget.

### Job Budget

Each site has a fixed allocated budget of 300 minutes per day. This is calculated as the sum of all job durations, from the moment the job has started until it finished. There are currently no restrictions around the number of schedules that can be created for any given site. If the daily budget is exhausted, running jobs are given a 15 minute grace period after which a timeout signal will be issued. No other jobs will be created that day until midnight UTC when all budgets are reset. In calculating the budget, partial minutes are rounded up.

```bash
terminus scheduledjobs:budget:info $SITE_NAME.$ENVIRONMENT

---------------------- ------------------------ -----------
 Daily Budget Elapsed   Daily Budget Remaining   Resets In
---------------------- ------------------------ -----------
 100m                   200m                     16h11m29s
---------------------- ------------------------ -----------
```

### Job Timeout

Timeouts are dynamic and dependent on the remaining budget plus a grace period. For instance, the daily available budget at the start of the day is 300 minutes, which means the first job's timeout is 315 minutes. When a job is launched throughout the day and the remaining budget is 60 minutes, the timeout will be calculated to 75 minutes.

### Email

Sending email via `sendmail` or `localhost` SMTP is not permitted. Email can still be sent via integrations with third party email providers either via their SMTP servers or API integrations.
