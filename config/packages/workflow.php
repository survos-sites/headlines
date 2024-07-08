<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;
use Survos\WorkflowBundle\Service\ConfigureFromAttributesService;
use App\EventListener\ValidateWorkflow;

return static function (FrameworkConfig $framework) {
//return static function (ContainerConfigurator $containerConfigurator): void {

    foreach ([
                 ValidateWorkflow::class,
             ] as $workflowClass) {
        ConfigureFromAttributesService::configureFramework($workflowClass, $framework, [$workflowClass]);
    }

};
