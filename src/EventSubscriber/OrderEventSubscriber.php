<?php

declare(strict_types=1);

namespace Drupal\commerce_order_mail\EventSubscriber;

use Drupal\commerce_order_mail\Service\OrderMailer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OrderEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly OrderMailer $mailer,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      "commerce_order.place.post_transition" => ["onOrderTransition", 0],
      "commerce_order.commerce_order.place.post_transition" => ["onOrderTransition", 0],
      "commerce_order.validate.post_transition" => ["onOrderTransition", 0],
      "commerce_order.commerce_order.validate.post_transition" => ["onOrderTransition", 0],
      "commerce_order.fulfillment.post_transition" => ["onOrderTransition", 0],
      "commerce_order.completed.post_transition" => ["onOrderTransition", 0],
    ];
  }

  public function onOrderTransition(WorkflowTransitionEvent $event): void {
    $config = $this->configFactory->get("commerce_order_mail.settings");
    if (!$config->get("enabled")) {
      return;
    }
    $triggerStates = $config->get("trigger_states") ?? ["place"];
    $transitionId = $event->getTransition() ? $event->getTransition()->getId() : "";
    $toState = "";
    try {
      $toState = $event->getTransition() ? $event->getTransition()->getToState()->getId() : "";
    }
    catch (\Throwable) {}

    $shouldSend = in_array($transitionId, (array) $triggerStates, TRUE) || in_array($toState, (array) $triggerStates, TRUE);
    if (!$shouldSend) {
      return;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    if (!$order) {
      return;
    }

    try {
      $this->mailer->sendOrderNotification($order);
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get("commerce_order_mail")->error("Failed to send order @id mail: @msg", ["@id" => $order->id(), "@msg" => $e->getMessage()]);
    }
  }

}
