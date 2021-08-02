<?php

namespace Drupal\autovalue_token\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AutoValueTokenForm.
 */
class AutoValueTokenForm extends FormBase {
  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'autovalue_token_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $autovalue_token = NULL)
  {
    if (!empty($autovalue_token)) {
      $item = autovalue_token_load($autovalue_token);
    }
    $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    $form['title'] = array(
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#default_value' => !empty($item->title) ? $item->title : "",
      '#required' => TRUE
    );
    $form['name'] = array(
      '#title' => t('Name'),
      '#type' => 'machine_name',
      '#default_value' => !empty($item->name) ? $item->name : "",
      '#required' => TRUE,
      '#machine_name' => [
        'source' => ['title'],
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ]
    );
    $form['start'] = array(
      '#title' => t('Start'),
      '#type' => 'textfield',
      '#default_value' => !empty($item->start) ? $item->start : 1,
      '#required' => TRUE
    );
    $form['value'] = array(
      '#title' => t('Value'),
      '#type' => 'textfield',
      '#default_value' => !empty($item->value) ? $item->value : "",
      '#required' => TRUE
    );
    $first_day = \Drupal::configFactory()->get('system.date')->get('first_day');
    $form['reset_schedule'] = array(
      '#title' => t('Schedule'),
      '#type' => 'select',
      '#default_value' => !empty($item->reset_schedule) ? $item->reset_schedule : "",
      '#options' => array(
        '' => t('-- None --'),
        'hourly' => t('Hourly'),
        'daily' => t('Daily (at midnight)'),
        'weekly' => t('Weekly (on @day)', array('@day' => $days[$first_day])),
        'monthly' => t('Monthly (on the 1st)'),
        'yearly' => t('Yearly (on 1st Jan)')
      ),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit')
    );
    return $form;
  }

  public function exists($value, $element, $form_state) {
    return autovalue_token_load($value);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $form_state->cleanValues();
    $values = (object)$form_state->getValues();
    autovalue_token_save($values);
    \Drupal::messenger()->addMessage(t("Auto value <strong>@name</strong> has been saved.", array('@name' => $values->name)));
    $form_state->setRedirect("autovalue_token.config");
  }
}
