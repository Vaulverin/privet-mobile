<?php
#region DB PROPS
const DB_HOST = 'localhost';
const DB_USER = 'homestead';
const DB_PWD = 'secret';
const DB_NAME = 'privet_mobile';
#endregion
#region Class Item
class Item
{
  /** @var int */
  private $id;
  /** @var string */
  private $name;
  /** @var int */
  private $parent;

  public function __construct(){}

  public static function fromRequest() : Item
  {
    $params = json_decode(file_get_contents('php://input'), true);
    if ($params == null) {
      throw new Exception('No params.');
    }
    $item = new Item();
    if (array_key_exists('name', $params)) {
      $item->name = trim($params['name']);
    }
    if (array_key_exists('parent', $params)) {
      $item->parent = (int)$params['parent'];
    }
    return $item;
  }
  private static $_connection;
  public static function connection() : PDO
  {
    if (static::$_connection == null) {
      static::$_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PWD);
    }
    return static::$_connection;
  }

  public static function getAll() : array
  {
    return static::connection()->query('select * from item', PDO::FETCH_ASSOC)->fetchAll();
  }
  #region Delete/Create/Update
  public static function delete(int $id) : int
  {
    if ($id < 1) {
      throw new Exception('Can not delete item with id = '.$id);
    }
    return static::connection()->exec('delete from item where id='.$id);
  }

  public function save(int $id = -1) : bool
  {
    if ($id == -1) {
      return $this->create();
    }
    else if ($id < 1) {
      throw new Exception('Can not change item with id = '.$id);
    }
    $this->id = $id;
    return $this->update();
  }

  protected function create() : bool
  {
    $this->validateAll();
    $stmt = static::connection()->prepare('insert into item (name, parent) values (:name, :parent)');
    $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
    $stmt->bindParam(':parent', $this->parent, PDO::PARAM_INT);
    return $stmt->execute();
  }

  protected function update() : bool
  {
    $this->validateName();
    $stmt = static::connection()->prepare('update item set (name=:name) where id=:id');
    $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $this->name, PDO::PARAM_STR);
    return $stmt->execute();
  }
  #endregion
  #region Validation
  public function validateName() : bool
  {
    if (empty($this->name)) {
      throw new Exception('Name can not be empty.');
    }
    return true;
  }
  public function validateParent() : bool
  {
    if ($this->parent < 0) {
      throw new Exception('Parent index can not be lower then 0.');
    }
    return true;
  }
  public function validateAll() : bool
  {
    return $this->validateName() && $this->validateParent();
  }
  #endregion
}
#endregion
/** @var mixed $response */
try {
  $id = (int)($_GET['id'] ?? -1);
  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $response = Item::getAll();
      break;
    case 'POST':
      $item = Item::fromRequest();
      $response = $item->save($id);
      break;
    case 'DELETE':
      $response = Item::delete($id);
      break;
  }
}
catch (Exception $e) {
  $response = ['error'=> $e->getMessage()];
}
echo json_encode($response);