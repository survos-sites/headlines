<?php

namespace App\EventListener;

use Survos\KeyValueBundle\Event\CsvHeaderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Event\SubmitEvent;

final class AppSpecificCsvHeaderEventListener
{
    #[AsEventListener(event: CsvHeaderEvent::class)]
    public function onCsvHeaderEvent(CsvHeaderEvent $event): void
    {
//        $event->header = array_map('strtoupper', $event->header);
//        dd($event, $event->header);
        // ...
    }
}
