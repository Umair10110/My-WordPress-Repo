<?php

$errorno = mysqli_connect_errno();
$errormsg = mysqli_connect_error();

$likely_misconfigured = $errorno == 1203;

if ($likely_misconfigured) {
  header("HTTP/1.1 529 Server Overloaded");
  nocache_headers();
  $header_msg = "Site Overloaded";
  $summary_msg = "This site has exhausted its connections to the database. Please try visiting at a later time.";
} else {
  http_response_code(500);
  nocache_headers();
  $header_msg = "Could not connect to database";
  $summary_msg = "This site is currently experiencing issues. Please try visiting at a later time.";
}

header("x-db-error-code: " . $errorno);
header("x-db-error-msg: " . $errormsg);

?>
<html><head>
  <title><?= $header_msg ?></title>
  <style>
    div {
      margin: 10px auto 10px;
      text-align: center!important;
      justify-content: center!important;
      background-color: #DDD;
      width: 50%;
      padding: 20px;
      border: 10px;
    }  
  </style>
</head>
<body style="background-color: #CCC;">
  <div style="font-weight: bold; font-size: 250%;"><?= $header_msg ?></div>
  <div><p style="font-weight: bold; font-size: 110%;"><?= $summary_msg ?></p>
  <? if ($likely_misconfigured) {
       if (defined('GD_RESELLER')) {
         if (GD_RESELLER == 1) {
           $mysql_help_url = "https://godaddy.com/help/a-41146";
         } else {
           $mysql_help_url = "https://www.secureserver.net/help/a-41146?pl_id=" . GD_RESELLER;
         }
       } else {
         $mysql_help_url = "https://www.secureserver.net/help/a-41146";
       }

    ?><p style="font-size: 90%">If this is your site, it is likely misconfigured or has a bad plugin installed.<br />
      Please visit <a href="<?= $mysql_help_url ?>"><?= $mysql_help_url ?></a> for tips on troubleshooting this issue.</p>
  <? } ?>
  </div>
  <div style="font-family: monospace">Error: <?= $errorno ?></div>
</body>
</html>
