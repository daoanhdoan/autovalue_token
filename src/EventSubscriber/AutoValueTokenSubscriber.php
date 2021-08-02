<?php

namespace Drupal\autovalue_token\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AutoValueTokenSubscriber.
 *
 * @package Drupal\autovalue_token
 */
class AutoValueTokenSubscriber implements EventSubscriberInterface {
  /**
   * @var DateFormatterInterface
   */
  protected $dateFormatter;
  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $dateFormatter) {
    $this->configFactory = $config_factory;
    $this->dateFormatter = $dateFormatter;
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['resetToken', 100];
    return $events;
  }

  /**
   * Perform the anonymous user redirection, if needed.
   *
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function resetToken(GetResponseEvent $event) {
    $query = \Drupal::database()->select('autovalue_token', 'av')
      ->fields('av');
    $results = $query->execute()->fetchAll();
    if ($results) {
      $request_time = \Drupal::time()->getRequestTime();
      foreach ($results as $item) {
        switch ($item->reset_schedule) {
          /*case 'minute';
            if ($this->dateFormatter->format($request_time, 'custom', 'YmdHi') != $this->dateFormatter->format($item->reset_timestamp, 'custom', 'YmdHi')) {
              \Drupal::database()->update('autovalue_token')
                ->fields(array('value' => 0, 'reset_timestamp' => \Drupal::time()->getRequestTime()))
                ->condition('name', $item->name)
                ->execute();
            }
            break;*/
          case 'hourly';
            if ($this->dateFormatter->format($request_time, 'custom', 'YmdH') != $this->dateFormatter->format($item->reset_timestamp, 'custom', 'YmdH')) {
              \Drupal::database()->update('autovalue_token')
                ->fields(array('value' => 0, 'reset_timestamp' => \Drupal::time()->getRequestTime()))
                ->condition('name', $item->name)
                ->execute();
            }
            break;
          case 'daily';
            if ($this->dateFormatter->format($request_time, 'custom', 'Yz') != $this->dateFormatter->format($item->reset_timestamp, 'custom', 'Yz')) {
              \Drupal::database()->update('autovalue_token')
                ->fields(array('value' => 0, 'reset_timestamp' => \Drupal::time()->getRequestTime()))
                ->condition('name', $item->name)
                ->execute();
            }
            break;
          case 'weekly';
            if ($this->dateFormatter->format($request_time, 'custom', 'YW') != $this->dateFormatter->format($item->reset_timestamp, 'custom', 'YW')) {
              $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
              $first_day = $this->configFactory->get('system.date')->get('first_day');
              $time = strtotime(t("@day this week", array('@day' => $days[$first_day])));
              \Drupal::database()->update('autovalue_token')
                ->fields(array('value' => 0, 'reset_timestamp' => $time))
                ->condition('name', $item->name)
                ->execute();
            }
            break;
            break;
          case 'monthly';
            if ($this->dateFormatter->format(\Drupal::time()->getRequestTime(), 'custom', 'Ym') != $this->dateFormatter->format($item->reset_timestamp, 'custom', 'Ym')) {
              \Drupal::database()->update('autovalue_token')
                ->fields(array('value' => 0, 'reset_timestamp' => strtotime("first day of this month")))
                ->condition('name', $item->name)
                ->execute();
            }
            break;
          case 'yearly';
            if ($this->dateFormatter->format(\Drupal::time()->getRequestTime(), 'custom', 'Y') != $this->dateFormatter->format($item->reset_timestamp, 'custom', 'Y')) {
              \Drupal::database()->update('autovalue_token')->fields(array('value' => 0, 'reset_timestamp' => strtotime("first day of january this year")))->condition('name', $item->name)->execute();
            }
            break;
            break;
        }
      }
    }
  }
}
