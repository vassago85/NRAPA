<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Member Compliance
|--------------------------------------------------------------------------
|
| Attribute-driven membership compliance rules. The activity year runs from
| 1 January to the deadline below; after that date new activity submissions
| for the same year are considered "late" and must be approved by an admin
| before they count toward dedicated-status compliance.
|
| Keep rules here as DATA so new policy years do not need a code change.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Activity year deadline
    |--------------------------------------------------------------------------
    |
    | The fixed cut-off for the current year's activity submissions. SAPS has
    | historically used 31 October. Overridable per environment via the
    | COMPLIANCE_DEADLINE_MONTH / COMPLIANCE_DEADLINE_DAY env vars so a late
    | policy change doesn't force a deploy.
    |
    */

    'deadline' => [
        'month' => (int) env('COMPLIANCE_DEADLINE_MONTH', 10),
        'day' => (int) env('COMPLIANCE_DEADLINE_DAY', 31),
    ],

];
