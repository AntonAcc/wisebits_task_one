<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class UserRequestListener
{
    public function onKernelView(ViewEvent $event): void
    {
        $user = $event->getControllerResult();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getMethod();

        if (in_array($method, ['PATCH', 'DELETE'], true) && $user->getDeleted() !== null) {
            throw new ItemNotFoundException('User not found');
        }
    }
}