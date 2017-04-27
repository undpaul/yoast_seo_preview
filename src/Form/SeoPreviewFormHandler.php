<?php

namespace Drupal\seo_preview\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for seo_preview form handlers.
 */
class SeoPreviewFormHandler implements EntityHandlerInterface {

  use DependencySerializationTrait;

  /**
   * The type of the entity for whose form the seo_preview form is used.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * SeoPreviewFormHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager,  RendererInterface $renderer) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityTypeManager =  $entity_type_manager;
    $this->renderer =  $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Renders a node preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node_preview
   * @param string $view_mode_id
   * @param null $langcode
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function preview(EntityInterface $node_preview, $view_mode_id = 'full', $langcode = NULL) {
    $node_preview->preview_view_mode = $view_mode_id;

    // Building preview, see NodePreviewController.
    $build = $this->entityTypeManager
      ->getViewBuilder($node_preview->getEntityTypeId())
      ->view($node_preview, $view_mode_id);

    $build['#entity_type'] = $node_preview->getEntityTypeId();
    $build['#' . $build['#entity_type']] = $node_preview;
    $build['#attached']['library'][] = 'node/drupal.node.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    return $this->renderer->render($build);
  }


  /**
   * Renders the title of a node in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node_preview
   *   The current node.
   *
   * @return string
   *   The  title.
   */
  public function previewTitle(EntityInterface $node_preview) {
    return $node_preview->label();
  }

  /**
   * Ajax Callback for returning node preview to seo library.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function previewSubmitAjax($form, FormStateInterface $form_state) {
    $node_preview = $form_state->getFormObject()->getEntity();
    $node_preview->in_preview = TRUE;

    $settings['seo_preview'] = [
      'body' => $this->preview($node_preview, 'full'),
      'pageTitle' => $this->previewTitle($node_preview),
    ];

    $response = new AjaxResponse();
    $response->addCommand(new SettingsCommand($settings, TRUE));
    $response->addCommand(new InvokeCommand('#edit-seo-preview-button', 'change'));
    return $response;
  }

  /**
   * Adds seo_preview details to the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {

    // Attach libraries and default settings.
    // @todo Add values to settings.
    $form['seo_preview'] = [
      '#type' => 'details',
      '#weight' => 99,
      '#title' => t('SEO preview'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'seo_preview/yoastseo',
          'seo_preview/seo_preview',
        ],
        'drupalSettings' => [
          'seo_preview' => [
            'body' => '',
            'pageTitle' => ''
          ]
        ]
      ],
    ];
    // Markup for yoaste_seo library output.
    // @todo: Add template.
    $form['seo_preview']['content'] = [
      '#markup' => '<div id="snippet"></div><div id="scores"></div><div id="output"></div><div id="preview-content"></div>',
    ];
    // Keyword field used by yoast_seo.
    $form['seo_preview']['keyword'] = [
      '#type' => 'textfield',
      '#title' => t('Focus keyword'),
      '#default_value' => isset($form_state->getUserInput()['keyword']) ? $form_state->getUserInput()['keyword'] : NULL,
      '#description' => t("Pick the main keyword or keyphrase that this post/page is about."),
    ];
    // Add preview button.
    $form['seo_preview']['seo_preview_button'] = [
      '#type' => 'button',
      '#value' => t('Seo preview'),
      '#ajax' => [
        'callback' => [$this, 'previewSubmitAjax'],
      ]
    ];

  }
}