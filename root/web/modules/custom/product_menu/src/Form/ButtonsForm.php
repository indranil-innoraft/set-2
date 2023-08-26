<?php

namespace Drupal\product_menu\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Html;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\product_menu\Database;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a product menu form.
 */
class ButtonsForm extends FormBase
{

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * The database connection.
   *
   * @var \Drupal\product_menu\Database
   */
  protected $productDatabase;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStorage;

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(
    AccountInterface $account,
    CurrentRouteMatch $cuurent_route,
    Database $product_database,
    PrivateTempStoreFactory $temp_storage,
    )
  {
    $this->account = $account;
    $this->currentRoute = $cuurent_route;
    $this->productDatabase = $product_database;
    $this->tempStorage = $temp_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    // Instantiates this form class.
    return new static(
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('product_menu.database'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'product_menu_buttons';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $node = $this->currentRoute->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $nid = $node->id();
    }
    $form['add_to_cart'] = [
      '#type' => 'button',
      '#value' => t('Add to Cart'),
      '#attributes' => [
        'node_id' => $nid,
        'class' => ['add-to-cart'],
      ],
      '#ajax' => [
        'callback' => '::addAjaxCallback',
      ],
      '#prefix' => '<div class=cart-logo></div>',
      '#suffix' => '<span class=message></span>',
    ];


    $form['buy_now'] = [
      '#type' => 'submit',
      '#value' => t('Buy Now'),
      '#submit' => ['::buyNowCallback'],
      '#attributes' => [
        'class' => ['buy_now'],
      ],
    ];

    $form['#attached']['library'][] = 'product_menu/product_menu_asset';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {

  }

  /**
   * Handeling the add to cart ajax response.
   *
   * @param array $form
   *   This contains the form elements.
   * @param FormStateInterface $form_state
   *   This contains the form values.
   *
   * @return AjaxResponse
   *   To show the user message that product added to the cart.
   */
  public function addAjaxCallback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $triggering_element = $form_state->getTriggeringElement();
    $node_id = $triggering_element['#attributes']['node_id'];
    $user_id = $this->currentUser()->id();
    if($this->productDatabase->addToCart($user_id, $node_id)) {
      $response->addCommand(new HtmlCommand('.message', t('Product has been added')));
    }
    return $response;
  }

  /**
   * Redirect the response to the thank you page.
   *
   * @param array $form
   *   This contains the form elements.
   * @param FormStateInterface $form_state
   *   This contains the form values.
   *
   * @return void
   *   Return nothing just redirect the response.
   */
  public function buyNowCallback(array $form, FormStateInterface $form_state)
  {
    $user_id = $this->currentUser()->id();
    $access_by_buy_now_button = base64_encode($user_id);
    $form_state->setRedirect('product_menu.thank_you', ['user_id' => $access_by_buy_now_button]);
  }
}
