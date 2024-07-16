<?php

namespace App\Workflow;

use Survos\PixieBundle\Event\RowEvent;
use Survos\PixieBundle\Model\Item;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;


// See events at https://symfony.com/doc/current/workflow.html#using-events

#[Workflow(supports: ['stdClass', Item::class], name: self::WORKFLOW_NAME)]
final class MexMusWorkflow implements MexMusWorkflowInterface
{

    public function __construct(
        // add services
    )
    {
    }

    #[AsGuardListener(self::WORKFLOW_NAME)]
    public function onGuard(GuardEvent $event): void
    {
        // switch ($event->getTransition()) { ...
    }

    #[AsTransitionListener(self::WORKFLOW_NAME)]
    public function onTransition(TransitionEvent $event): void
    {
        switch ($event->getTransition()->getName()) {
            case self::TRANSITION_ENHANCE:
                dd($event->getSubject());
                // dispatch
                break;
        }
    }

    #[AsEventListener(event: RowEvent::class)]
    public function onRowEvent(RowEvent $event): void
    {
        static $mun = []; // the array of municipios
        static $estado = []; // the array of states
        $row = $event->row;
        switch ($event->type) {
            case $event::POST_LOAD:
                ksort($mun);
                ksort($estado);
                dd($mun, $estado);
                break;
            case $event::LOAD:
                if ($facebook = $row['facebook']??null) {
                    $parseUrl = parse_url($facebook);
                    $row['facebook'] = trim($parseUrl['path'],'/');
//                    https://www.facebook.com/MuseoGuadalupePosada?fref=ts
                }
                $mun[$row['municipio_id']] = $row['municipio'];
                $estado[$row['estado_id']] = $row['estado'];
//                dd($row, $event->tableName, $event->key, $mun);
                // create the
        }
        // ...
    }

}
