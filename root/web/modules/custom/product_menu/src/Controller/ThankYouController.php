<?php

namespace Drupal\product_menu\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\product_menu\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns responses for product menu routes.
 */
class ThankYouController extends ControllerBase {

  /**
   * It contains node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;

  /**
   * It contains the file storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $fileStorage;

  /**
   * It contains the database service.
   *
   * @var \Drupal\product_menu\Database
   */
  protected Database $productDatabase;

  /**
   * It contains the route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $currentRoute;




  /**
   * The controller constructor.
   */
  public function __construct(
    Database $product_database,
    EntityTypeManager $entity_type_manager,
    RouteMatchInterface $current_route,

  ) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->productDatabase = $product_database;
    $this->currentRoute = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('product_menu.database'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * Builds the response.
   */
  public function build(string $user_id) {
    $user_account_name = \Drupal::currentUser()->getAccountName();
    $user_account_id = \Drupal::currentUser()->id();
    $product_array = $this->productDatabase->getAllProducts($user_account_id);
    $product_count = count($product_array);
    $products = $this->nodeStorage->loadMultiple($product_array);
    $items = [];
    foreach($products as $product) {
      $image = $this->fileStorage
        ->load(
          $product->get('field_images'
          )
        ->target_id)
        ->createFileUrl();
      $item = [
        'title' => $product->get('title')->value,
        'image' => $image,
      ];
      array_push($items, $item);
    }

    $build = [
      '#theme' => 'thank-you-page',
      '#user' => t($user_account_name),
      '#items' => $items,
      '#items_count' => $product_count,
    ];

    return $build;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account)
  {
    $user_id_encrypeted = $this->currentRoute->getParameter('user_id');
    $access_by_buy_now_button = base64_decode($user_id_encrypeted);
    if($access_by_buy_now_button === $account->id()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
