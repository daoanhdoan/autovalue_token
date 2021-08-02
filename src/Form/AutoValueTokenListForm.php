<?php

namespace Drupal\autovalue_token\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class AutoValueTokenListForm.
 */
class AutoValueTokenListForm extends FormBase
{


  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'autovalue_token_list_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $query = \Drupal::database()->select('autovalue_token', 'av')->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('av')
      ->limit(50)
      ->addTag('autovalue_token_access');
    $results = $query->execute()->fetchAll();
    $options = array();
    $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    $first_day = \Drupal::configFactory()->get('system.date')->get('first_day');
    $schedules = array(
      '' => t('-- None --'),
      'hourly' => t('Hourly'),
      'daily' => t('Daily (at midnight)'),
      'weekly' => t('Weekly (on @day)', array('@day' => $days[$first_day])),
      'monthly' => t('Monthly (on the 1st)'),
      'yearly' => t('Yearly (on 1st Jan)')
    );

    foreach ($results as $item) {
      $options[$item->name] = array(
        'title' => array(
          'data' => array(
            '#title' => !empty($item->title) ? $item->title : $item->name,
            '#type' => 'link',
            '#url' => Url::fromRoute("autovalue_token.edit", ['autovalue_token' => $item->name]),
          )
        ),
        'name' => $item->name,
        'start' => $item->start,
        'value' => $item->value,
        'reset_schedule' => $schedules[$item->reset_schedule]
      );
    }
    $header = array(
      'title' => t('Title'),
      'name' => t('Name'),
      'start' => t('Start Value'),
      'value' => t('Current Value'),
      'reset_schedule' => t('When to reset'),
    );
    $form['list'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No content available.'),
      '#require' => TRUE
    );
    $form['autovalue_token'] = array(
      '#type' => 'value',
    );

    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
      '#submit' => array([$this, 'reset'])
    );
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array([$this, 'clear'])
    );
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO: Implement submitForm() method.
  }

  public function reset($form, FormStateInterface &$form_state)
  {
    $list = $form_state->getValue('list');
    if ($list) {
      foreach ($list as $name => $selected) {
        if ($selected) {
          $item = autovalue_token_load($name);
          switch ($item->reset_schedule) {
            case 'hourly';
            case 'daily';
              $reset_timestamp = \Drupal::time()->getRequestTime();
              break;
            case 'weekly';
              $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
              $first_day = $this->configFactory->get('system.date')->get('first_day');
              $reset_timestamp = strtotime(t("@day this week", array('@day' => $days[$first_day])));
              break;
            case 'monthly';
              $reset_timestamp = strtotime("first day of this month");
              break;
            case 'yearly';
              $reset_timestamp = strtotime("first day of january this year");
              break;
          }

          $item->reset_timestamp = $reset_timestamp;
          $item->value = 0;

          autovalue_token_save($item);
          \Drupal::messenger()->addMessage(t("Auto value <strong>@name</strong> has been reset.", array('@name' => $name)));
        }
      }
    }
  }

  public function clear($form, FormStateInterface &$form_state)
  {
    $list = $form_state->getValue('list');
    if ($list) {
      foreach ($list as $name => $selected) {
        if ($selected) {
          autovalue_token_delete($name);
          \Drupal::messenger()->addMessage(t("Auto value <strong>@name</strong> has been deleted.", array('@name' => $name)));
        }
      }
    }
  }
}
