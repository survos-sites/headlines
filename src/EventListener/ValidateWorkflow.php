<?php

/**
 * Validate the .yaml and pixie databases for a Config object
 *
 */
namespace App\EventListener;

use Survos\WorkflowBundle\Attribute\Transition;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\TransitionEvent;

final class ValidateWorkflow
{
    const string PLACE_NEW='new';
    const string PLACE_VALID='valid';
    const string PLACE_INVALID='invalid';
    #[Transition(self::PLACE_NEW, self::PLACE_VALID)]
    const string TRANSITION_VALIDATE='validate';

    #[Transition([self::PLACE_VALID, self::PLACE_NEW], self::PLACE_INVALID)]
    const string TRANSITION_INVALIDATE='invalidate';

    #[AsEventListener(event: 'workflow.transition')]
    public function onWorkflowTransition(TransitionEvent $event): void
    {
        dd($event);
        // ...
    }
}
