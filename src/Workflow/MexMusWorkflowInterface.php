<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Transition;

// See events at https://symfony.com/doc/current/workflow.html#using-events

interface MexMusWorkflowInterface
{
    // This name is used for injecting the workflow into a service
    // #[Target(MexMusWorkflowInterface::WORKFLOW_NAME)] private WorkflowInterface $workflow
    public const WORKFLOW_NAME = 'MexMusWorkflow';

    public const PLACE_NEW = 'new';
    public const PLACE_ENHANCED = 'enhanced';

    #[Transition([self::PLACE_NEW], self::PLACE_ENHANCED)]
    public const TRANSITION_ENHANCE = 'enhance';
}
