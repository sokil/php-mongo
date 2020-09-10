<?php
declare(strict_types=1);

namespace Sokil\Mongo\EventFactory;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventFactoryInterface
{
    public function createConstructAfterEvent() : StoppableEventInterface;
    public function createDeleteAfterEvent() : StoppableEventInterface;
    public function createDeleteBeforeEvent() : StoppableEventInterface;
    public function createInsertAfterEvent() : StoppableEventInterface;
    public function createInsertBeforeEvent() : StoppableEventInterface;
    public function createSaveAfterEvent() : StoppableEventInterface;
    public function createSaveBeforeEvent() : StoppableEventInterface;
    public function createUpdateAfterEvent() : StoppableEventInterface;
    public function createUpdateBeforeEvent() : StoppableEventInterface;
    public function createValidateAfterEvent() : StoppableEventInterface;
    public function createValidateBeforeEvent() : StoppableEventInterface;
    public function createValidateErrorEvent() : StoppableEventInterface;
}