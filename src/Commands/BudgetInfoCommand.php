<?php

namespace Pantheon\TerminusScheduledJobs\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\TerminusScheduledJobs\ScheduledJobsApi\ScheduledJobsClientAwareTrait;

class BudgetInfoCommand extends TerminusCommand implements RequestAwareInterface, SiteAwareInterface
{
    use ScheduledJobsClientAwareTrait;
    use SiteAwareTrait;

    /**
     * Displays the budget info accessible to the currently logged-in user.
     *
     * @authorize
     * @filter-output
     *
     * @command scheduledjobs:budget:info
     *
     * @field-labels
     *     elapsed_budget: Daily Budget Elapsed
     *     remaining_budget: Daily Budget Remaining
     *     resets_in: Resets In
     *
     * @param string $site_id Either a site's UUID or its name or site_env.
     * @return RowsOfFields
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function budgetInfo(string $site_id)
    {
        $site = $this->getSite($site_id);
        try {
            $budgetInfo = $this->getClient()->budgetInfo($site->id);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error listing budget info: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }
        $budgetInfos = [(array)$budgetInfo];
        return new RowsOfFields($budgetInfos);
    }
}
