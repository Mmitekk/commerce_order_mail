<?php

declare(strict_types=1);

namespace Drupal\commerce_order_mail\Service;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

final class OrderMailer {

  use StringTranslationTrait;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly \Drupal\Core\Utility\Token $token,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly LanguageManagerInterface $languageManager,
    private readonly \Drupal\Core\Mail\MailManagerInterface $mailManager,
  ) {}

  public function sendOrderNotification(OrderInterface $order, bool $isTest = FALSE): bool {
    $config = $this->configFactory->get("commerce_order_mail.settings");
    if (!$isTest && !$config->get("enabled")) {
      return FALSE;
    }
    $recipients = $this->parseRecipients((string) $config->get("recipient"));
    if (!$isTest && empty($recipients)) {
      $this->loggerFactory->get("commerce_order_mail")->error("Recipient not configured.");
      return FALSE;
    }
    if ($isTest) {
      $recipients = $recipients ?: [$config->get("sender")];
    }

    $tokenData = ["commerce_order" => $order, "site" => []];
    $subjectTemplate = (string) $config->get("subject_template");
    $bodyTemplate = (string) $config->get("body_template");
    $subject = $this->token->replace($subjectTemplate, $tokenData, ["clear" => TRUE]);
    $body = $this->token->replace($bodyTemplate, $tokenData, ["clear" => TRUE]);

    $sender = (string) $config->get("sender");
    $senderName = (string) $config->get("sender_name");
    $bodyFormat = (string) ($config->get("body_format") ?: "text/html");
    $isHtml = str_contains($bodyFormat, "html");

    if (!$isHtml) {
      $subject = strip_tags($subject);
    }

    $smtpHost = (string) $config->get("smtp_host");
    $smtpPort = (int) $config->get("smtp_port");
    $smtpUser = (string) $config->get("smtp_username");
    $smtpPass = (string) $config->get("smtp_password");
    $smtpEnc = (string) $config->get("smtp_encryption");
    $smtpTimeout = (int) ($config->get("smtp_timeout") ?: 15);
    $allowSelfSigned = (bool) $config->get("smtp_allow_self_signed");

    $useCustomSmtp = $smtpHost !== "" && $smtpPort > 0;

    $success = TRUE;
    foreach ($recipients as $to) {
      $ok = $useCustomSmtp
        ? $this->sendViaSmtp($to, $sender, $senderName, $subject, $body, $isHtml, $smtpHost, $smtpPort, $smtpEnc, $smtpUser, $smtpPass, $smtpTimeout, $allowSelfSigned)
        : $this->sendViaMailManager($to, $sender, $subject, $body, $isHtml);
      if (!$ok) {
        $success = FALSE;
      }
    }

    if ($config->get("send_copy_to_customer") && !$isTest) {
      $customerMail = $order->getEmail();
      if ($customerMail && !in_array($customerMail, $recipients, TRUE)) {
        $ok = $useCustomSmtp
          ? $this->sendViaSmtp($customerMail, $sender, $senderName, $subject, $body, $isHtml, $smtpHost, $smtpPort, $smtpEnc, $smtpUser, $smtpPass, $smtpTimeout, $allowSelfSigned)
          : $this->sendViaMailManager($customerMail, $sender, $subject, $body, $isHtml);
        if (!$ok) {
          $success = FALSE;
        }
      }
    }

    if ($success && $config->get("log_success")) {
      $this->loggerFactory->get("commerce_order_mail")->info("Order @num notification sent to @recipients.", ["@num" => $order->getOrderNumber() ?: $order->id(), "@recipients" => implode(", ", $recipients)]);
    }

    return $success;
  }

  private function sendViaSmtp(string $to, string $from, string $fromName, string $subject, string $body, bool $isHtml, string $host, int $port, string $encryption, string $user, string $pass, int $timeout, bool $allowSelfSigned): bool {
    try {
      $useDsn = FALSE;
      $transport = NULL;
      if ($allowSelfSigned && $encryption !== "none") {
        try {
          $scheme = $encryption === "ssl" ? "smtps" : "smtp";
          $creds = $user !== "" ? rawurlencode($user) . ":" . rawurlencode($pass) . "@" : "";
          $dsn = sprintf("%s://%s%s:%d", $scheme, $creds, $host, $port);
          $query = [];
          if ($encryption === "tls") {
            $query[] = "encryption=tls";
          }
          $query[] = "verify_peer=0";
          $dsn .= "?" . implode("&", $query);
          $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
          $useDsn = TRUE;
        }
        catch (\Throwable) {
          $transport = NULL;
          $useDsn = FALSE;
        }
      }
      if (!$useDsn) {
        $transport = new EsmtpTransport($host, $port, $encryption === "ssl");
        if (($encryption === "tls" || $encryption === "ssl") && method_exists($transport, "setStreamOptions")) {
          $transport->setStreamOptions(["ssl" => ["verify_peer" => !$allowSelfSigned, "verify_peer_name" => !$allowSelfSigned, "allow_self_signed" => $allowSelfSigned]]);
        }
        if ($user !== "") {
          $transport->setUsername($user);
          $transport->setPassword($pass);
        }
        if (method_exists($transport, "setTimeout")) {
          $transport->setTimeout($timeout);
        }
      }

      $mailer = new Mailer($transport);
      $email = (new Email())
        ->from($fromName ? sprintf("%s <%s>", $fromName, $from) : $from)
        ->to($to)
        ->subject($subject);
      if ($isHtml) {
        $email->html($body);
        $email->text(strip_tags(preg_replace("#<br\s*/?>#i", "\n", $body) ?? $body));
      }
      else {
        $email->text($body);
      }
      $mailer->send($email);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get("commerce_order_mail")->error("SMTP send failed to @to: @msg", ["@to" => $to, "@msg" => $e->getMessage()]);
      return FALSE;
    }
  }

  private function sendViaMailManager(string $to, string $from, string $subject, string $body, bool $isHtml): bool {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $params = ["subject" => $subject, "body" => $body, "is_html" => $isHtml, "from" => $from];
    $result = $this->mailManager->mail("commerce_order_mail", "order_notification", $to, $langcode, $params, $from, TRUE);
    return (bool) ($result["result"] ?? FALSE);
  }

  public function sendTest(): bool {
    $order = $this->getSampleOrder();
    return $this->sendOrderNotification($order, TRUE);
  }

  private function getSampleOrder(): OrderInterface {
    $storage = \Drupal::entityTypeManager()->getStorage("commerce_order");
    $ids = $storage->getQuery()->accessCheck(FALSE)->sort("order_id", "DESC")->range(0, 1)->execute();
    if ($ids) {
      $order = $storage->load(reset($ids));
      if ($order instanceof OrderInterface) {
        return $order;
      }
    }
    $orderType = \Drupal::entityTypeManager()->getStorage("commerce_order_type")->load("default");
    if (!$orderType) {
      $types = \Drupal::entityTypeManager()->getStorage("commerce_order_type")->getQuery()->accessCheck(FALSE)->range(0, 1)->execute();
      $orderType = $types ? \Drupal::entityTypeManager()->getStorage("commerce_order_type")->load(reset($types)) : NULL;
    }
    $typeId = $orderType ? $orderType->id() : "default";
    /** @var OrderInterface $order */
    $order = $storage->create(["type" => $typeId, "order_number" => "TEST-0001", "mail" => \Drupal::currentUser()->getEmail() ?: "test@example.com", "state" => "place"]);
    return $order;
  }

  private function parseRecipients(string $raw): array {
    $parts = array_map("trim", explode(",", $raw));
    $parts = array_filter($parts, fn($v) => $v !== "" && str_contains($v, "@"));
    return array_values(array_unique($parts));
  }

}
