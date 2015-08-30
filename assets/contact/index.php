<?php
/**
 * MicroEngine MailForm
 * http://microengine.jp/mailform/
 *
 * Copyright 2011-2014, MicroEngine Inc. (http://microengine.jp)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (C) 2011-2014 MicroEngine Inc.
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version 0.3.2
 */
require_once('me_mailform/define.php');

// DATA_ROOT定義
if (!defined('DATA_ROOT')) {
    $DATA_ROOT = dirname(__FILE__) . '/me_mailform/data';
    if (is_dir($DATA_ROOT)) {
        define('DATA_ROOT', $DATA_ROOT);
    } else {
        die('データディレクトリが見つかりません。/ data directory not found.');
    }
}

require_once('me_mailform/script/Me_MailForm.php');
$me_MailForm = new Me_MailForm();
$me_MailForm->run();
