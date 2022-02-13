<?php
/**
 * Created by PhpStorm.
 * User: Chip
 * Date: 12/3/2017
 * Time: 1:20 PM
 */
require_once "dbconnect.php";
require_once "random_compat/lib/random.php";

class DigitalDownload {
  const DAILY_DOWNLOAD_LIMIT = 5;

  public $ID;
  public $UID;
  public $DOID;
  public $IPAddress;
  public $Token;
  public $Active;
  public $Created;

  public function __construct() {
    if (!isset($this->Token)) {
      $this->Token = bin2hex(random_bytes(16));
    }
    if (!isset($this->Active)) {
      $this->Active = true;
    }
    if (!isset($this->IPAddress)) {
      $this->IPAddress = $_SERVER['REMOTE_ADDR'];
    }
  }
}

class DigitalDownloadDao {

  /**
   * @param DigitalDownload|$digitalDownload
   * @return bool|int
   */
  static function insert(DigitalDownload $digitalDownload) {
    global $connection;

    $query = $connection->prepare(
        "INSERT INTO digital_downloads (UID, DOID, IPAddress, Token, Active) VALUES (?, ?, ?, ?, ?)");
    $query->execute([
        $digitalDownload->UID,
        $digitalDownload->DOID,
        $digitalDownload->IPAddress,
        $digitalDownload->Token,
        $digitalDownload->Active
    ]);
    if ($query->rowCount() > 0) {
      return $connection->lastInsertId(); // Return ID on success
    } else {
      return false; // Returns false on failure
    }
  }

  /**
   * @param $UID
   * @param $DOID
   * @return bool
   */
  static function deactivateAllByUserAndOrder($UID, $DOID) {
    global $connection;

    $q = $connection->prepare(
        "UPDATE digital_downloads SET Active = FALSE WHERE UID = ? AND DOID = ?");
    $q->execute([$UID, $DOID]);

    return $q->rowCount() > 0;
  }

  /**
   * @param $UID
   * @param $DOID
   * @param $sinceHours
   * @return array|DigitalDownload[]
   */
  static function getByUserAndOrderId($UID, $DOID, $sinceHours) {
    global $connection;

    $q = $connection->prepare(
        "SELECT * FROM digital_downloads WHERE UID = ? AND DOID = ? AND Created >= CURRENT_TIMESTAMP() - INTERVAL ? HOUR");
    $q->execute([$UID, $DOID, $sinceHours]);
    return $q->fetchAll(PDO::FETCH_CLASS, DigitalDownload::class);
  }

  /**
   * @param $UID
   * @param $DOID
   * @return null|object|DigitalDownload
   */
  static function getLastActiveByUserAndOrderId($UID, $DOID) {
    global $connection;

    $q = $connection->prepare(
        "SELECT * FROM digital_downloads WHERE UID = ? AND DOID = ? AND ACTIVE = TRUE LIMIT 1");
    $q->execute([$UID, $DOID]);
    return $q->fetchObject(DigitalDownload::class);
  }

  /**
   * @param $UID
   * @param $token
   * @return null|object|DigitalDownload
   */
  static function getActiveByUserAndToken($UID, $token) {
    global $connection;

    $q = $connection->prepare("SELECT * FROM digital_downloads WHERE UID = ? AND Token = ? AND Active = TRUE");
    $q->execute([$UID, $token]);
    return $q->fetchObject(DigitalDownload::class);
  }
}