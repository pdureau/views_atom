<?php

/**
 * @file
 * Definition of Drupal\views_atom\Plugin\views\row\AtomFields.
 */

namespace Drupal\views_atom\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Renders an Atom item based on fields.
 *
 * @ViewsRow(
 *   id = "atom_fields",
 *   title = @Translation("Atom Fields"),
 *   help = @Translation("Display fields as Atom items."),
 *   theme = "views_view_row_atom",
 *   display_types = {"feed"}
 * )
 */
class AtomFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['id_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['updated_date_field'] = ['default' => ''];
    $options['link_field'] = ['default' => ''];
    $options['content_field'] = ['default' => ''];
    $options['author_name'] = ['default' => ''];
    $options['author_link'] = ['default' => ''];
    $options['published_date_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['id_field'] = [
      '#type' => 'select',
      '#title' => $this->t('ID field'),
      '#description' => $this->t('The globally unique identifier of the Atom item.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['id_field'],
      '#required' => TRUE,
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The field that is going to be used as the Atom item title for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['updated_date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Last update date field'),
      // Source: http://www.faqs.org/rfcs/rfc3339.html
      '#description' => $this->t('The field that is going to be used as the Atom item updated. It needs to be in RFC 3339 format.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['updated_date_field'],
      '#required' => TRUE,
    ];
    $form['link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#description' => $this->t('The field that is going to be used as the Atom item alternate HTML link. This must be a drupal relative path.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#required' => FALSE,
    ];
    $form['content_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Content field'),
      '#description' => $this->t('The field that is going to be used as the Atom item content.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['content_field'],
      '#required' => FALSE,
    ];
    $form['author_name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Author name field'),
      '#description' => $this->t('The field that is going to be used as the author name for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['author_name_field'],
      '#required' => FALSE,
    ];
    $form['author_link_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Author link field'),
      '#description' => $this->t('The field that is going to be used as the author link for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['author_link_field'],
      '#required' => FALSE,
    ];
    $form['published_date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication date field'),
      // Source: http://www.faqs.org/rfcs/rfc3339.html
      '#description' => $this->t('The field that is going to be used as the Atom item published. It needs to be in RFC 3339 format.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['published_date_field'],
      '#required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    // Source: http://atomenabled.org/developers/syndication/#requiredEntryElements
    $required_options = ['id_field', 'title_field', 'updated_date_field'];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for Atom item.');
        break;
      }
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }

    if (function_exists('rdf_get_namespaces')) {
      // TODO: utiliser RDF pour ajouter des triplets <link/>.
      // $item->rdf = False;
    }

    // Create the Atom item object.
    $item = new \stdClass();
    $item->id = "urn:uuid:" . $this->getField($row_index, $this->options['id_field']);
    $item->title = $this->getField($row_index, $this->options['title_field']);
    // $item->title = mb_convert_encoding($item->title, "UTF-8", "HTML-ENTITIES");
    // @todo Views should expect and store a leading /. See:
    // https://www.drupal.org/node/2423913
    $item->link_self = FALSE;
    $item->link_alternate_html = $this->getField($row_index, $this->options['link_field']);
    $item->content = $this->getField($row_index, $this->options['content_field']);
    $item->published = $this->getField($row_index, $this->options['published_date_field']);
    $item->updated = $this->getField($row_index, $this->options['updated_date_field']);
    $item->author_name = $this->getField($row_index, $this->options['author_name_field']);
    $item->author_link = $this->getField($row_index, $this->options['author_link_field']);

    $row_index++;

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];
    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param $field_id
   *   The ID assigned to the required field in the display.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return $this->view->style_plugin->getField($index, $field_id);
  }

}
