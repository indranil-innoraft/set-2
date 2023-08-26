<?php

namespace Drupal\product_menu;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * @todo Add class description.
 */
class Database {

  /**
   * It contains the database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * It contains the account of the current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $CurrentUser;

  /**
   * It contains the node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $node;

  /**
   * Constructs a Database object.
   */
  public function __construct(
    Connection $connection,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->connection = $connection;
    $this->CurrentUser = $current_user;
    $this->node = $entity_type_manager->getStorage('node');
  }

  /**
   * Add the product to the database.
   *
   * @param integer $user_id
   *    It contains the user id.
   * @param integer $node_id
   *    It contains the product node id.
   *
   * @return boolean
   *    It return true if product added to the cart or else return false.
   */
  public function addToCart(int $user_id, int $node_id) {
    if($this->isProductAllreadyAdded($user_id, $node_id)) {
      return false;
    }
    elseif($this->isUserAddedFirstTime($user_id) === 0) {
      $node_id_array[] = $node_id;
      $data = [
        'user_id' => $user_id,
        'product_id' => serialize($node_id_array),
      ];
      $query = $this->connection->insert('cart')->fields(['user_id', 'product_id']);
      $query->values($data);
      $query->execute();
    }
    else {
      $query = $this->connection->select('cart');
      $query->addField('cart', 'product_id');
      $query->condition('user_id', $user_id, '=');
      $products = $query->execute()
        ->fetchField();

      $product_array = unserialize($products);
      array_push($product_array, $node_id);
      $num_updated = $this->connection->update('cart')
        ->fields([
          'product_id' => serialize($product_array),
        ])
        ->condition('user_id', $user_id, '=')
        ->execute();
    }
    return true;
  }

  /**
   * Check current user already added the product in the database or not.
   *
   * @param integer $user_id
   *    It contains the user id.
   * @param integer $node_id
   *    It contains the product node id.
   *
   * @return boolean
   *   It return true if product alredy added or else false.
   */
  public function isProductAllreadyAdded(int $user_id, int $node_id) {
    $query = $this->connection->select('cart');
    $query->addField('cart', 'product_id');
    $query->condition('user_id', $user_id, '=');
    $products = $query->execute()
    ->fetchField();

    $product_array = unserialize($products);
    foreach ($product_array as $key => $value) {
      if($value === $node_id)
        return true;
    }
    return false;
  }

  /**
   * Check the user is added the product first time or not.
   *
   * @param integer $user_id
   *   It contains the user id.
   *
   * @return int
   *    It return the field count.
   */
  public function isUserAddedFirstTime(int $user_id) {
    $fields_count = $this->connection->select('cart')
      ->fields('cart', ['user_id'])
      ->condition('user_id', $user_id, '=')
      ->execute()
      ->fetchField();

    return (int) $fields_count;
  }

  /**
   * Get all the product form the cart table based on user id.
   *
   * @param integer $user_id
   *   The current user id.
   *
   * @return array
   *   This is a product array.
   */
  public function getAllProducts(int $user_id) {
    $query = $this->connection->select('cart');
    $query->addField('cart', 'product_id');
    $query->condition('user_id', $user_id, '=');
    $products = $query->execute()
      ->fetchField();
    $product_array = unserialize($products);
    return $product_array;
  }
}


