<?php

namespace Drupal\kcvv_vv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kcvv_vv\KvccVoetbalVlaanderenApiServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class KcvvVvForm.
 */
class KcvvVvForm extends FormBase {

  /**
   * Drupal\kcvv_vv\KvccVoetbalVlaanderenApiServiceInterface definition.
   *
   * @var \Drupal\kcvv_vv\KvccVoetbalVlaanderenApiServiceInterface
   */
  protected $kcvvVvVoetbalvlaanderen;

  /**
   * Constructs a new KcvvVvForm object.
   */
  public function __construct(
    KvccVoetbalVlaanderenApiServiceInterface $kcvv_vv_voetbalvlaanderen
  ) {
    $this->kcvvVvVoetbalvlaanderen = $kcvv_vv_voetbalvlaanderen;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('kcvv_vv.voetbalvlaanderen')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kcvv_vv_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<p>' . t('Push the button below to sync players in our db with VoetbalVlaanderen statistics.') . '</p>',
    ];
    $form['run'] = [
      '#type' => 'submit',
      '#value' => t('Sync'),
      '#submit' => ['::runCron'],
    ];

    return $form;
  }

  /**
   * Form submission handler for running cron manually.
   */
  public function runCron(array &$form, FormStateInterface $form_state) {
    if ($count = $this->kcvvVvVoetbalvlaanderen->syncPlayersAllTeams()) {
      $this->messenger()->addStatus($this->t('Synchronized @count player(s).', ['@count' => $count]));
    }
    else {
      $this->messenger()->addError($this->t('Failed to synchronize player statistics.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  // phpcs:disable
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  // phpcs:enable

  // phpcs:disable
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }
  // phpcs:enable

}
