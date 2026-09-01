<?php

declare(strict_types=1);

namespace Drupal\commerce_order_mail\Controller;

use Drupal\commerce_order_mail\Service\OrderMailer;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class TestController extends ControllerBase {

  public function __construct(private readonly OrderMailer $mailer) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get("commerce_order_mail.mailer"));
  }

  public function sendTest(): array {
    $ok = $this->mailer->sendTest();
    if ($ok) {
      $this->messenger()->addStatus($this->t("Тестовое письмо отправлено. Проверьте почту получателя. / Test email sent."));
    }
    else {
      $this->messenger()->addError($this->t("Не удалось отправить тестовое письмо. Смотрите логи (admin/reports/dblog). / Failed to send test email. Check logs."));
    }
    return $this->redirect("commerce_order_mail.settings");
  }

}
