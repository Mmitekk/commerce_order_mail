<?php

declare(strict_types=1);

namespace Drupal\commerce_order_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class SettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return "commerce_order_mail_settings";
  }

  protected function getEditableConfigNames(): array {
    return ["commerce_order_mail.settings"];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config("commerce_order_mail.settings");

    $form["intro"] = [
      "#type" => "markup",
      "#markup" => "<div class=\"messages messages--warning\"><strong>Важно (29.06.2026):</strong> Яндекс отключил бесплатный SMTP/IMAP/POP3. Для отправки с сайта используйте внешний SMTP — Gmail (App Password / OAuth2), Mail.ru, корпоративный SMTP, или сервис транзакционной почты (SendGrid, Mailgun, Brevo). Этот модуль отправляет напрямую через указанный SMTP, минуя smtp.yandex.ru.<br><strong>Important (2026-06-29):</strong> Yandex disabled free SMTP/IMAP/POP3. Use external SMTP — Gmail (App Password/OAuth2), etc.</div>",
    ];

    $form["enabled"] = [
      "#type" => "checkbox",
      "#title" => $this->t("Включить отправку уведомлений / Enable notifications"),
      "#default_value" => $config->get("enabled"),
    ];

    $form["recipients"] = [
      "#type" => "fieldset",
      "#title" => $this->t("Получатели / Recipients"),
    ];
    $form["recipients"]["recipient"] = [
      "#type" => "textfield",
      "#title" => $this->t("Email получателя(ей) (через запятую) / Recipient email(s) comma-separated"),
      "#default_value" => $config->get("recipient"),
      "#description" => $this->t("Куда доставлять сабмиты заказов. Пример: manager@example.com, info@example.com"),
      "#required" => TRUE,
    ];
    $form["recipients"]["sender"] = [
      "#type" => "email",
      "#title" => $this->t("Email отправителя / Sender email"),
      "#default_value" => $config->get("sender"),
      "#description" => $this->t("Должен совпадать с SMTP-пользователем (для Gmail — тот же Google-аккаунт). Рекомендуется совпадать с доменом сайта для SPF/DKIM."),
      "#required" => TRUE,
    ];
    $form["recipients"]["sender_name"] = [
      "#type" => "textfield",
      "#title" => $this->t("Имя отправителя / Sender name"),
      "#default_value" => $config->get("sender_name"),
      "#description" => $this->t("Например: Магазин Example"),
    ];

    $form["smtp"] = [
      "#type" => "details",
      "#title" => $this->t("SMTP / IMAP настройки (авторизационные данные)"),
      "#open" => TRUE,
      "#description" => $this->t("Укажите данные Google-аккаунта или любого SMTP. Для Gmail: host=smtp.gmail.com, port=587, encryption=tls, user=ваш@gmail.com, password=App Password (16 символов) — создаётся в myaccount.google.com > Безопасность > Пароли приложений. IMAP для чтения не требуется для отправки, но если нужен — imap.gmail.com:993/ssl."),
    ];
    $form["smtp"]["smtp_host"] = [
      "#type" => "textfield",
      "#title" => $this->t("SMTP host"),
      "#default_value" => $config->get("smtp_host"),
      "#required" => TRUE,
      "#placeholder" => "smtp.gmail.com",
    ];
    $form["smtp"]["smtp_port"] = [
      "#type" => "number",
      "#title" => $this->t("SMTP port"),
      "#default_value" => $config->get("smtp_port"),
      "#required" => TRUE,
      "#min" => 1,
      "#max" => 65535,
      "#description" => $this->t("Gmail: 587 (TLS) или 465 (SSL). Mail.ru: 465. Яндекс 360 платный: smtp.yandex.ru 465."),
    ];
    $form["smtp"]["smtp_encryption"] = [
      "#type" => "select",
      "#title" => $this->t("Шифрование / Encryption"),
      "#options" => ["none" => $this->t("None"), "tls" => "TLS (STARTTLS)", "ssl" => "SSL"],
      "#default_value" => $config->get("smtp_encryption"),
    ];
    $form["smtp"]["smtp_username"] = [
      "#type" => "textfield",
      "#title" => $this->t("SMTP пользователь / Username (Google account email)"),
      "#default_value" => $config->get("smtp_username"),
      "#description" => $this->t("Для Gmail — полный адрес Gmail. Для других — логин SMTP."),
    ];
    $form["smtp"]["smtp_password"] = [
      "#type" => "password",
      "#title" => $this->t("SMTP пароль / Password or App Password"),
      "#description" => $this->t("Оставьте пустым чтобы не менять. Для Gmail используйте App Password, не обычный пароль."),
      "#attributes" => ["autocomplete" => "new-password"],
    ];
    $form["smtp"]["smtp_allow_self_signed"] = [
      "#type" => "checkbox",
      "#title" => $this->t("Разрешить самоподписанные сертификаты"),
      "#default_value" => $config->get("smtp_allow_self_signed"),
    ];
    $form["smtp"]["smtp_timeout"] = [
      "#type" => "number",
      "#title" => $this->t("Таймаут (сек)"),
      "#default_value" => $config->get("smtp_timeout") ?? 15,
      "#min" => 5,
      "#max" => 60,
    ];

    $form["templates"] = [
      "#type" => "details",
      "#title" => $this->t("Шаблоны письма / Email templates"),
      "#open" => TRUE,
    ];
    $form["templates"]["subject_template"] = [
      "#type" => "textfield",
      "#title" => $this->t("Шаблон темы / Subject template"),
      "#default_value" => $config->get("subject_template"),
      "#required" => TRUE,
      "#maxlength" => 255,
    ];
    $form["templates"]["body_template"] = [
      "#type" => "textarea",
      "#title" => $this->t("Шаблон тела письма / Body template"),
      "#default_value" => $config->get("body_template"),
      "#rows" => 16,
      "#required" => TRUE,
    ];
    $form["templates"]["body_format"] = [
      "#type" => "select",
      "#title" => $this->t("Формат тела"),
      "#options" => ["text/html" => $this->t("HTML"), "text/plain" => $this->t("Plain text")],
      "#default_value" => $config->get("body_format"),
    ];
    $form["templates"]["token_help"] = [
      "#type" => "markup",
      "#markup" => "<details><summary>" . $this->t("Доступные токены Commerce / Available tokens") . "</summary>" .
        "<p>" . $this->t("Используйте токены модуля Commerce для Drupal 10:") . "</p><ul>" .
        "<li><code>[commerce_order:order_number]</code> — " . $this->t("Order number") . "</li>" .
        "<li><code>[commerce_order:total_price]</code> — " . $this->t("Total price") . "</li>" .
        "<li><code>[commerce_order:mail]</code> — " . $this->t("Customer email") . "</li>" .
        "<li><code>[commerce_order:created]</code> — " . $this->t("Created date") . "</li>" .
        "<li><code>[commerce_order:state]</code> — " . $this->t("Order state") . "</li>" .
        "<li><code>[commerce_order:order_items]</code> — " . $this->t("Items (default)") . "</li>" .
        "<li><code>[commerce_order:order_items_table]</code> — " . $this->t("HTML table (provided by this module)") . "</li>" .
        "<li><code>[commerce_order:order_items_text]</code> — " . $this->t("Plain text items") . "</li>" .
        "<li><code>[commerce_order:billing_profile]</code>, <code>[commerce_order:shipping_profile]</code>, <code>[site:name]</code>, <code>[site:url]</code></li>" .
        "</ul><p>" . $this->t("Полный список: /admin/help/token") . " — включите модуль Token.</p></details>",
    ];
    if (\Drupal::moduleHandler()->moduleExists("token")) {
      $form["templates"]["token_tree"] = [
        "#theme" => "token_tree_link",
        "#token_types" => ["commerce_order", "site", "user"],
        "#show_restricted" => FALSE,
      ];
    }

    $form["behavior"] = [
      "#type" => "details",
      "#title" => $this->t("Поведение / Behavior"),
      "#open" => FALSE,
    ];
    $form["behavior"]["trigger_states"] = [
      "#type" => "checkboxes",
      "#title" => $this->t("Отправлять при переходе в состояние / Trigger on state"),
      "#options" => ["draft" => "draft", "validate" => "validate", "place" => "place", "fulfillment" => "fulfillment", "completed" => "completed"],
      "#default_value" => $config->get("trigger_states") ?? ["place"],
      "#description" => $this->t("Обычно достаточно «place» (размещение заказа)."),
    ];
    $form["behavior"]["send_copy_to_customer"] = [
      "#type" => "checkbox",
      "#title" => $this->t("Отправлять копию покупателю / Send copy to customer"),
      "#default_value" => $config->get("send_copy_to_customer"),
    ];
    $form["behavior"]["log_success"] = [
      "#type" => "checkbox",
      "#title" => $this->t("Логировать успешные отправки"),
      "#default_value" => $config->get("log_success"),
    ];

    $form["actions"]["test"] = [
      "#type" => "link",
      "#title" => $this->t("Отправить тестовое письмо"),
      "#url" => \Drupal\Core\Url::fromRoute("commerce_order_mail.test"),
      "#attributes" => ["class" => ["button", "button--secondary"]],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config("commerce_order_mail.settings");
    $trigger = array_filter($form_state->getValue("trigger_states") ?? []);
    $config->set("enabled", (bool) $form_state->getValue("enabled"))
      ->set("recipient", $form_state->getValue("recipient"))
      ->set("sender", $form_state->getValue("sender"))
      ->set("sender_name", $form_state->getValue("sender_name"))
      ->set("smtp_host", $form_state->getValue("smtp_host"))
      ->set("smtp_port", (int) $form_state->getValue("smtp_port"))
      ->set("smtp_encryption", $form_state->getValue("smtp_encryption"))
      ->set("smtp_username", $form_state->getValue("smtp_username"))
      ->set("smtp_allow_self_signed", (bool) $form_state->getValue("smtp_allow_self_signed"))
      ->set("smtp_timeout", (int) $form_state->getValue("smtp_timeout"))
      ->set("subject_template", $form_state->getValue("subject_template"))
      ->set("body_template", $form_state->getValue("body_template"))
      ->set("body_format", $form_state->getValue("body_format"))
      ->set("trigger_states", array_values($trigger))
      ->set("send_copy_to_customer", (bool) $form_state->getValue("send_copy_to_customer"))
      ->set("log_success", (bool) $form_state->getValue("log_success"));
    $pwd = $form_state->getValue("smtp_password");
    if ($pwd !== "" && $pwd !== NULL) {
      $config->set("smtp_password", $pwd);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
