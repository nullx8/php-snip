<?php

include_once('human.inc.php');

if (!isset($_REQUEST['amount'])) {
        die("usage ?amount=1&rate=1&periods=12");
}
echo CompoundInterest($_REQUEST['amount'],$_REQUEST['rate'],$_REQUEST['periods']);
