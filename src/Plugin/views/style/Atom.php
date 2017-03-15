<?php

/**
 * @file
 * Definition of Drupal\views_atom\Plugin\views\style\Atom.
 */

namespace Drupal\views_atom\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Default style plugin to render an Atom feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "atom",
 *   title = @Translation("Atom Feed"),
 *   help = @Translation("Generates an Atom feed from a view."),
 *   theme = "views_view_atom",
 *   display_types = {"feed"}
 * )
 */
class Atom extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    // Add the Atom icon to the view.
    $this->view->feedIcons[] = [
      '#theme' => 'feed_icon',
      '#url' => $url,
      '#title' => $title,
    ];

    // Attach a link to the Atom feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'application/atom+xml',
      'title' => $this->t("This content is also avalaible as an Atom feed"),
      'href' => $url,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['description'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->options['description'],
      '#description' => $this->t('This will appear in the Atom feed itself.'),
      '#maxlength' => 1024,
    ];
  }

  /**
   * Get Atom feed description.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getDescription() {
    $description = $this->options['description'];
    // Allow substitutions from the first row.
    $description = $this->tokenizeValue($description, 0);
    return $description;
  }

  /**
   * Get Atom feed last udpate.
   *
   * @return string
   *   The string containing the date
   */
  public function getUpdated() {
    $plugin = $this->view->rowPlugin;
    $updated = "";
    foreach ($this->view->result as $row_index => $row) {
      // TODO1: comparer les dates pour prendre la plus récente.
      $updated = $plugin->getField($row_index, $plugin->options['updated_date_field']);
    }
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->rowPlugin)) {
      debug('Drupal\views_atom\Plugin\views\style\Atom: Missing row plugin');
      return [];
    }
    $rows = [];
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
    unset($this->view->row_index);
    return $build;
  }

}
