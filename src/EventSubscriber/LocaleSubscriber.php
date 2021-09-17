<?php

namespace App\EventSubscriber;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $defaultLocale = 'en')
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($locale = $request->query->get('_locale')) {
            $request->setLocale($request->query->get('_locale'));
            $request->getSession()->set('_locale', $locale);
        } elseif ($request->getSession()->get('_locale')) {
            $request->setLocale($request->getSession()->get('_locale'));
        }
    }

    #[ArrayShape([KernelEvents::REQUEST => "array[]"])] public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
