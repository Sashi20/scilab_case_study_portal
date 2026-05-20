<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ScilabCaseStudyRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = _list_of_case_study();
    $url_case_study_id = (int) arg(2);
    $case_study_data = _case_study_information($url_case_study_id);
    if ($case_study_data == 'Not found') {
      $url_case_study_id = '';
    } //$case_study_data == 'Not found'
    if (!$url_case_study_id) {
      $selected = !$form_state->getValue(['case_study']) ? $form_state->getValue(['case_study']) : key($options_first);
    } //!$url_case_study_id
    elseif ($url_case_study_id == '') {
      $selected = 0;
    } //$url_case_study_id == ''
    else {
      $selected = $url_case_study_id;
    }
    $form = [];
    $form['case_study'] = [
      '#type' => 'select',
      '#title' => t('Title of the case study'),
      '#options' => _list_of_case_study(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => 'case_study_project_details_callback'
        ],
    ];
    if (!$url_case_study_id) {
      $form['case_study_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_case_study_details"></div>',
      ];
      $form['selected_case_study'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_case_study"></div>',
      ];
    } //!$url_case_study_id
    else {
      $case_study_default_value = $url_case_study_id;
      $form['case_study_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_case_study_details">' . _case_study_details($case_study_default_value) . '</div>',
      ];
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $form['selected_case_study'] = array(
      // 			'#type' => 'item',
      // 			'#markup' => '<div id="ajax_selected_case_study">' . l('Download Report(PDF)', "case-study-project/download/final-report/" . $case_study_default_value) . '<br>' . l('Download Case Files and Report', 'case-study-project/full-download/project/' . $case_study_default_value) . '</div>'
      // 		);

    }
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

}
