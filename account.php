<?php
require_once "php/user.php";
require_once "php/dbcontrol.php";
require_once "php/RegUser.php";
require_once "php/DigitalOrder.php";

// Redirect to index if not logged in
if (!isset($User)) {
  header('Location: signin.php');
  exit();
}

$regUser = RegUserDao::getByEmail($User);
if (!$regUser) {
  header('Location: index.php');
  exit();
}

// Lazy settings update
$savedContactPref = false;
if (isset($_POST["Contact"])) {
  $contact = $_POST["Contact"];
  if (RegUser::isValidContactPref($contact)) {
    $regUser->Contact = $contact;
  }
  RegUserDao::updateContactPref($regUser);
  $savedContactPref = true;
}

$digitalOrders = DigitalOrderDao::getAllByUser($regUser->UID);

$claimedOrders = array();
$unclaimedOrders = array();

foreach ($digitalOrders as $o) {
  if ($o->Claimed) {
    $claimedOrders[] = $o;
  } else {
    $unclaimedOrders[] = $o;
  }
}

$navActive = "none";
require_once "header.php";
?>
<div class="content">
  <div id="top" class="fp-header-container">
    <div class="fp-page-header">
      My Account
      <div class="fp-sub-header"><?php echo $User; ?></div>
    </div>
  </div>
  <div class="row account-section-margin">
    <div class="col-md-3"><span style="font-size:150%">Preferences</span></div>
    <div class="col-md-9">
      <form action="account.php" method="post">
        <div class="form-check">
          <input id="check1" type="radio" name="Contact" class="form-check-input"
                 value="<?= RegUser::CONTACT_PREF_NEWSLETTER ?>" <?= ($regUser->isReceivingNewsletter()) ? "checked" : "" ?>>
          <label for="check1" class="form-check-label">Receive one newsletter e-mail per month. No spam, guaranteed. Opt out any time.</label>
        </div>
        <div class="form-check">
          <input id="check2" type="radio" name="Contact" class="form-check-input"
                 value="<?= RegUser::CONTACT_PREF_DEFAULT ?>" <?= (!$regUser->isReceivingNewsletter()) ? "checked" : "" ?>>
          <label for="check2" class="form-check-label">Opt out of the newsletter. You will only receive order-related messages (order confirmation, receipts, etc.).</label>
        </div>
        <p></p>
        <button type="submit" class="btn btn-sm btn-primary">Update Email Preferences</button>
        <?php
        if ($savedContactPref) {
          echo "<span class='fa fa-check' style='color:green'></span> Saved";
        }
        ?>
      </form>
    </div>
  </div>

  <div class="row account-section-margin">
    <div class="col-md-3"><span style="font-size:150%">Digital Downloads</span></div>
    <div class="col-md">
      <?php
      if (empty($claimedOrders)) {
        echo "<i>No digital downloads ready</i>";
      } else {
        foreach ($claimedOrders as $o) {
          ?>
          <div class="card mb-3 border-primary">
            <div class="card-header font-weight-bold"><?= $o->getTypeName() ?></div>
            <div class="card-body text-center p-1">
            <form action="digital_download.php" method="post">
              <input type="hidden" id="order-token" name="orderToken" value="<?= $o->OrderToken ?>">
              <button type="submit" class="btn btn-sm btn-primary my-2" <?= ($o->Revoked) ? "disabled" : "" ?>>
                Download PDF
              </button>
              <small class="form-text text-muted"><?= ($o->Revoked) ? "<i>This key has been suspended, please contact us</i>" : "" ?></small>
            </form>
            </div>
            <?php
            if (!empty($o->getAdditionalLinks())) {
              echo "<div class=\"card-footer\">Additional Content<ul>";
              foreach ($o->getAdditionalLinks() as $text => $link) {
                ?>
                <li><a href="<?=$link?>" target="_blank"><?=$text?></a></li>
                <?php
              }
              echo "</ul></div>";
            }
            ?>
          </div>
          <?php
        }
      }
      ?>
    </div>
  </div>

  <div class="row account-section-margin">
    <div class="col-md-3"><span style="font-size:150%">Unclaimed Keys</span></div>
    <div class="col-md">
      <?php
      if (empty($unclaimedOrders)) {
        echo "<i>No unclaimed digital downloads</i>";
      } else {
        foreach ($unclaimedOrders as $o) {
          ?>
          <div class="card mb-3 border-info">
            <div class="card-header"><?= $o->getTypeName() ?></div>
            <div class="card-body text-center">
              <b>Key:</b> <?= $o->OrderToken ?>
            <form action="redeem_code.php" method="post">
              <input type="hidden" id="code" name="code" value="<?= $o->OrderToken ?>">
              <input type="hidden" id="csrf" name="csrf"
                     value="<?= hash_hmac('sha256', $o->OrderToken, "PrefillCheck") ?>">
              <button type="submit" class="btn btn-sm btn-primary" <?= ($o->Revoked) ? "disabled" : "" ?>>
                Claim for Myself
              </button>
              <small class="form-text text-muted"><?= ($o->Revoked) ? "<i>This key has been suspended, please contact us</i>" : "" ?></small>
            </form>
            </div>
          </div>
          <?php
        }
      }
      ?>
    </div>
  </div>

  <div class="row account-section-margin">
    <div class="col-md-3"><span style="font-size:150%">Redeem Key</span></div>
    <div class="col-md">
      <form id="redeem-form" class="form-inline" action="redeem_code.php" method="post">
        <label for="code" class="my-2 mr-2">Product Key:</label>
        <input required type="text" class="form-control my-2 mr-2" id="code"
                 name="code" placeholder="Digital Product Key">
        <button type="submit" class="btn btn-primary my-2">Redeem Key</button>
        <div class="w-100">
          <div class="g-recaptcha" data-sitekey="6LeR0RwUAAAAAHq89xqIPYBx7LqoNl7CiJx1rlpW"></div>
        </div>
      </form>
    </div>
  </div>

  <div class="row account-section-margin">
    <div class="col-md-3">
      <span style="font-size:150%">Password</span>
    </div>
    <div class="col-md">
      <p><a class="btn btn-primary" href="changepw.php">Change Password</a></p>
    </div>
  </div>

</div>
<?php require_once "footer.php"; ?>
