<?php
require_once "dbconnect.php";
require_once "random_compat/lib/random.php";
require_once "PdfProduct.php";


class DigitalOrder {

  public $ID;
  public $UID;
  public $OID;
  public $OrderToken;
  public $OrderSource;
  public $Claimed;
  public $Revoked;
  public $Created;
  public $Type;

  /** @var PdfProduct */
  public $pdfProduct;

  /**
   * DigitalOrder constructor.
   */
  function __construct() {
    if (!isset($this->UID)) {
      $this->UID = 0;
    }
    if (!isset($this->OID)) {
      $this->OID = 0;
    }
    if (!isset($this->OrderToken)) {
      $this->OrderToken = bin2hex(random_bytes(12));
    }
    if (!isset($this->Claimed)) {
      $this->Claimed = 0;
    }
    if (!isset($this->Revoked)) {
      $this->Revoked = 0;
    }
  }

  /**
   * @return int
   */
  public function getType() {
    return $this->Type;
  }

  /**
   * @return string
   */
  public function getTypeName() {
    return $this->pdfProduct->display_name;
  }

  /**
   * @return array
   */
  public function getAdditionalLinks() {
    return $this->pdfProduct->getAdditionalLinks();
  }
}

class DigitalOrderDao {

  /**
   * @param DigitalOrder|$digitalOrder
   * @return bool|int
   */
  static function insert(DigitalOrder $digitalOrder) {
    global $connection;

    $query = $connection->prepare(
        "INSERT INTO digital_orders (UID, OID, Type, OrderToken, OrderSource, Claimed, Revoked) VALUES (?,?,?,?,?,?,?)");
    $query->execute([
        $digitalOrder->UID,
        $digitalOrder->OID,
        $digitalOrder->Type,
        $digitalOrder->OrderToken,
        $digitalOrder->OrderSource,
        $digitalOrder->Claimed,
        $digitalOrder->Revoked
    ]);
    if ($query->rowCount() > 0) {
      return $connection->lastInsertId(); // Return ID on success
    }
    return false; // Returns false on failure
  }

  /**
   * @param $id
   * @return null|object|DigitalOrder
   */
  static function getById($id) {
    global $connection;

    $q = $connection->prepare("SELECT * FROM digital_orders WHERE ID = ?");
    $q->execute([$id]);
    /** @var DigitalOrder $do */
    $do = $q->fetchObject(DigitalOrder::class);
    if (!$do) {
      return null;
    }
    $do->pdfProduct = PdfProductDao::getById($do->Type);
    return $do;
  }

  /**
   * @param $UID
   * @return array|DigitalOrder[]
   */
  static function getAllByUser($UID) {
    global $connection;

    $q = $connection->prepare("SELECT * FROM digital_orders WHERE UID = ?");
    $q->execute([$UID]);
    /** @var DigitalOrder[] $dos */
    $dos = $q->fetchAll(PDO::FETCH_CLASS, DigitalOrder::class);
    /** @var PdfProduct[] $allPdfs */
    $allPdfs = array();
    foreach (PdfProductDao::getAllPdfProducts() as $pdf) {
      $allPdfs[$pdf->id] = $pdf;
    }
    foreach ($dos as $do) {
      $do->pdfProduct = $allPdfs[$do->Type];
    }
    return $dos;
  }

  /**
   * @param $orderToken
   * @return object|DigitalOrder
   */
  static function getByOrderToken($orderToken) {
    global $connection;

    $q = $connection->prepare("SELECT * FROM digital_orders WHERE OrderToken = ?");
    $q->execute([$orderToken]);
    $do = $q->fetchObject(DigitalOrder::class);
    if (!$do) {
      return null;
    }
    $do->pdfProduct = PdfProductDao::getById($do->Type);
    return $do;
  }

  static function getClaimedByUserAndOrderToken($UID, $orderToken) {
    global $connection;

    $q = $connection->prepare("SELECT * FROM digital_orders WHERE UID = ? AND OrderToken = ? AND Claimed = TRUE AND Revoked = FALSE");
    $q->execute([$UID, $orderToken]);
    $do = $q->fetchObject(DigitalOrder::class);
    if (!$do) {
      return null;
    }
    $do->pdfProduct = PdfProductDao::getById($do->Type);
    return $do;
  }

  /**
   * @param $UID
   * @param $code
   * @return bool
   */
  static function claimCode($UID, $code) {
    global $connection;

    $q = $connection->prepare(
        "UPDATE digital_orders SET UID = ?, Claimed = TRUE WHERE Claimed = FALSE AND Revoked = FALSE AND OrderToken = ? LIMIT 1");
    $q->execute([$UID, $code]);
    return $q->rowCount() > 0;
  }
}