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
 * @version 0.3.4
 */
require_once('simple_html_dom.php');
/**
 * メールフォーム
 */
class Me_MailForm
{
    /** システム文字コード */
    const SYSTEM_CHAR_CODE = 'UTF-8';
    /** 入力ステップ名 */
    const ENTRY = 'entry';
    /** 確認ステップ名 */
    const CONFIRM = 'confirm';
    /** 送信ステップ名 */
    const SEND = 'send';
    /** エラーメッセージ用要素のIDにつける接尾辞 */
    const ERROR_ID_SUFFIX = '_error';
    /** ステップパラメータ用 name属性の値 */
    const STEP_PARAMETER = '_step';
    /** 戻るボタン用 name属性の値 */
    const BACK_PARAMETER = '_back';
    /** 設定ファイルパス */
    const CONFIG_FILE = '/config/config.ini';
    /** アイテム（フォーム項目）設定ファイルパス */
    const ITEM_FILE = '/config/item.ini';
    /** メールログディレクトリ */
    const MAIL_LOG_DIR = '/log/';
    /** メールログファイル名 */
    const MAIL_LOG_FILENAME = 'qdmail.log';
    /** メールエラーログファイル名 */
    const MAIL_ERROR_LOG_FILENAME = 'qdmail_error.log';
    /** CAPTCHA画像パス */
    const CAPTCHA_IMAGE_PATH = 'me_mailform/public/captcha_image.php';
    /** メール文字セット */
    const MAIL_CHARSET = 'ISO-2022-JP';
    /** メールエンコード */
    const MAIL_ENCODING = '7bit';

    /**
     * 設定配列
     * @var array
     */
    private $config;
    /**
     * アイテム（フォーム項目）配列
     * @var array
     */
    private $form_item;
    /**
     * ステップ名
     * @var string
     */
    private $step;
    /**
     * INPUT要素の値更新フラグ
     * @var boolean
     */
    private $update_value = false;
    /**
     * エラー発生フラグ
     * @var boolean
     */
    private $is_error = false;
    /**
     * 文字コード変換フラグ
     */
    private $convert_char_code = false;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // 内部文字コード指定
        mb_internal_encoding(self::SYSTEM_CHAR_CODE);

        // 設定ファイル読み込み
        $this->config = parse_ini_file(DATA_ROOT . self::CONFIG_FILE, true);
        $this->form_item = parse_ini_file(DATA_ROOT . self::ITEM_FILE, true);

        // キャッシュ設定
        $cache_limiter = 'private_no_expire';
        if (strlen($this->config['global']['session.cache_limiter']) > 0) {
            $cache_limiter = $this->config['global']['session.cache_limiter'];
        }
        session_cache_limiter($cache_limiter);
        
        // kcaptchaでセッションを使用する
        session_start();

        // PHP_VERSION_IDを定義
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
        }

        // エラーレベル設定
        if (isset($this->config['global']['debug']) && $this->config['global']['debug']) {
            if (PHP_VERSION_ID >= 50400) {
                error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
            } else {
                error_reporting(E_ALL ^ E_NOTICE);
            }
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
        }

        // magic_quotes_gpc対策
        if (get_magic_quotes_gpc()) {
            $_POST = $this->against_magic_quotes($_POST);
        }

        // viewファイルの文字コード設定
        if (strlen($this->config['global']['char_code']) === 0) {
            $this->config['global']['char_code'] = self::SYSTEM_CHAR_CODE;
        }
        if ($this->config['global']['char_code'] !== self::SYSTEM_CHAR_CODE) {
            $this->convert_char_code = true;
        }
    }

    /**
     * アクション実行
     */
    public function run()
    {
        // ステップ判定
        $this->set_step();

        // postされた値を受け取る
        foreach ($this->form_item as $name => $prop) {
            if (isset($_POST[$name])) {
                if ($this->convert_char_code) {
                    $this->form_item[$name]['value'] = mb_convert_encoding($_POST[$name], self::SYSTEM_CHAR_CODE, $this->config['global']['char_code']);
                } else {
                    $this->form_item[$name]['value'] = $_POST[$name];
                }
            }
        }
        
        if ($this->step !== self::ENTRY) {
            // convert
            $this->convert();
            // validation
            $this->validate();
        }

        // ステップ毎の処理
        switch ($this->step) {
            case self::ENTRY:
                $this->entry();
                break;
            case self::CONFIRM:
                $this->confirm();
                break;
            case self::SEND:
                $this->send();
                break;
            default:
                break;
        }
    }

    /**
     * ステップ判定
     */
    private function set_step()
    {
        switch ($_POST[self::STEP_PARAMETER]) {
            case self::SEND:
                $this->step = self::SEND;
                break;
            case self::CONFIRM:
                $this->step = self::CONFIRM;
                break;
            case self::ENTRY:
            default:
                $this->step = self::ENTRY;
                break;
        }
        // 戻るボタンが押された場合
        if (isset($_POST[self::BACK_PARAMETER])) {
            $this->step = self::ENTRY;
            $this->update_value = true;
        }
    }

    /**
     * 次のステップ名を返す
     * @return string $next_step
     */
    private function get_next_step()
    {
        $next_step = self::SEND;
        if ($this->step === self::ENTRY && $this->config['flow']['use_confirm']) {
            $next_step = self::CONFIRM;
        }
        return $next_step;
    }

    /**
     * 入力ステップ処理
     */
    private function entry()
    {
        // テンプレートを取得
        $template = $this->get_template($this->config['step'][$this->step]);
        $html = str_get_html($template, true, true, self::SYSTEM_CHAR_CODE, false);

        // フォーム要素を取得
        $form = null;
        if (strlen($this->config['global']['form_name']) > 0) {
            $form = $html->find('form[name=' . $this->config['global']['form_name'] . ']', 0);
        }
        if ($form === null) {
            // form_name設定が空もしくは、該当のformが無い場合は一つ目のformを対象とする。
            $form = $html->find('form', 0);
        }

        // action属性が空の場合は現在のURLを指定する
        if (strlen($form->action) < 1) {
            $form->action = $this->html_escape($_SERVER['REQUEST_URI']);
        }

        foreach ($this->form_item as $name => $item) {
            // エラー発生時もしくは、戻るボタンが押された場合は、INPUT要素の値を書き換える。
            // 初回アクセス時は、書き換えないのでテンプレートの状態が初期値となる。
            if ($this->update_value) {
                switch ($item['type']) {
                    case 'textarea':
                        $form->find('textarea[name=' . $name . ']', 0)->innertext = $this->html_escape($item['value']);
                        break;
                    case 'select':
                        if ($item['multiple']) {
                            $option_list = $form->find('select[name=' . $name . '[]]', 0)->find('option');
                            foreach ($option_list as $option) {
                                $selected = null;
                                if (is_array($item['value'])) {
                                    foreach ($item['value'] as $val) {
                                        if ($option->value === $val) {
                                            $selected = (strlen($val) > 0) ? 'selected' : null;
                                            continue;
                                        }
                                    }
                                }
                                $option->selected = $selected;
                            }
                        } else {
                            $form->find('select[name=' . $name . ']', 0)->find('option[selected=selected]', 0)->selected = null;
                            $form->find('select[name=' . $name . ']', 0)->find('option[value=' . $item['value'] . ']', 0)->selected = 'selected';
                        }
                        break;
                    case 'radio':
                        foreach ($form->find('input[name=' . $name . ']') as $radio) {
                            if ($radio->value === $item['value']) {
                                $radio->checked = 'checked';
                            } else if ($radio->checked !== null) {
                                $radio->checked = null;
                            }
                        }
                        break;
                    case 'checkbox':
                        if ($item['multiple']) {
                            $checkbox_list = $form->find('input[name=' . $name . '[]]');
                            foreach ($checkbox_list as $checkbox) {
                                $checked = null;
                                if (is_array($item['value'])) {
                                    foreach ($item['value'] as $val) {
                                        if ($checkbox->value === $val) {
                                            $checked = (strlen($val) > 0) ? 'checked' : null;
                                            continue;
                                        }
                                    }
                                }
                                $checkbox->checked = $checked;
                            }
                        } else {
                            $checked = (strlen($item['value']) > 0) ? 'checked' : null;
                            $form->find('input[name=' . $name . ']', 0)->checked = $checked;
                        }
                        break;
                    case 'captcha':
                        // 初期状態のままにする。
                        break;
                    default:
                        $form->find('input[name=' . $name . ']', 0)->value = $this->html_escape($item['value']);
                        break;
                }
            }

            // CAPTCHA処理
            if ($item['type'] === 'captcha') {
                $form->find('#' . $name . '_image', 0)->src = self::CAPTCHA_IMAGE_PATH;
            }

            // フォーム項目名にサフィックスを追加したID名を持つ要素をテンプレート内に用意しておく
            // エラー発生時はその要素の中にエラーメッセージを表示する
            // エラーが発生しなかったら、その要素を削除する
            if (strlen($item['error']) > 0) {
                $form->find('#' . $name . self::ERROR_ID_SUFFIX, 0)->innertext = $item['error'];
            } else {
                $form->find('#' . $name . self::ERROR_ID_SUFFIX, 0)->outertext = '';
            }
        }


        // エラーメッセージ見出し
        // 入力画面のテンプレートには、エラー発生を知らせるための表示をしておく。
        // エラーが発生しなかったら、その要素を削除する。
        if (!$this->is_error) {
            $html->find('#' . $this->config['message']['error_message_id'], 0)->outertext = '';
        }

        // ステップパラメータをフォームに追加
        $form->innertext .= $this->get_step_parameter();

        // HTML書き出し
        $this->render($html);
    }

    /**
     * 確認ステップ処理
     */
    private function confirm()
    {
        // テンプレートを取得
        $template = $this->get_template($this->config['step'][$this->step]);
        $html = str_get_html($template, true, true, self::SYSTEM_CHAR_CODE, false);

        // ステップパラメータを取得
        $hidden = $this->get_step_parameter();

        foreach ($this->form_item as $name => $item) {
            switch ($item['type']) {
                case 'textarea':
                    $html->find('#' . $name, 0)->innertext = $this->nl2br_escape($item['value']);
                    break;
                case 'select':
                    if ($item['multiple']) {
                        $item_value = '';
                        if (is_array($item['value'])) {
                            $item_value = implode($this->config['select']['delimiter'], $item['value']);
                            // hidden要素生成
                            foreach ($item['value'] as $val) {
                                $hidden .= '<input type="hidden" name="' . $name . '[]" value="'
                                    . $this->html_escape($val) . '"' . $this->self_closing_tag() . '>';
                            }
                        }
                        $html->find('#' . $name, 0)->innertext = $this->nl2br_escape($item_value);
                        // hiddenは生成済みなのでループを抜ける。
                        continue 2;
                    } else {
                        $html->find('#' . $name, 0)->innertext = $this->html_escape($item['value']);
                    }
                    break;
                case 'checkbox':
                    if ($item['multiple']) {
                        $item_value = '';
                        if (is_array($item['value'])) {
                            $item_value = implode($this->config['checkbox']['delimiter'], $item['value']);
                            // hidden要素生成
                            foreach ($item['value'] as $val) {
                                $hidden .= '<input type="hidden" name="' . $name . '[]" value="'
                                    . $this->html_escape($val) . '"' . $this->self_closing_tag() . '>';
                            }
                        }
                        $html->find('#' . $name, 0)->innertext = $this->nl2br_escape($item_value);
                        // hiddenは生成済みなのでループを抜ける。
                        continue 2;
                    } else {
                        $html->find('#' . $name, 0)->innertext = $this->html_escape($item['value']);
                    }
                    break;
                default:
                    $html->find('#' . $name, 0)->innertext = $this->html_escape($item['value']);
                    break;
            }
            // hidden要素生成
            $hidden .= '<input type="hidden" name="' . $name . '" value="'
                . $this->html_escape($item['value']) . '"' . $this->self_closing_tag() . '>';
        }
        
        // フォーム要素を取得
        $form = null;
        if (strlen($this->config['global']['form_name']) > 0) {
            $form = $html->find('form[name=' . $this->config['global']['form_name'] . ']', 0);
        }
        if ($form === null) {
            // form_name設定が空もしくは、該当のformが無い場合は一つ目のformを対象とする。
            $form = $html->find('form', 0);
        }

        // action属性が空の場合は現在のURLを指定する
        if (strlen($form->action) < 1) {
            $form->action = $this->html_escape($_SERVER['REQUEST_URI']);
        }

        // hidden要素をフォームに追加
        $form->innertext .= $hidden;

        // HTML書き出し
        $this->render($html);
    }

    /**
     * 送信ステップ
     */
    private function send()
    {
        require_once('qdmail.php');
        require_once('qdsmtp.php');

        $to = array_map(array(__CLASS__, 'multi_to_address'), explode(',', $this->config['mail']['to']));
        $from = array($this->config['mail']['from'], $this->config['mail']['from_name']);
        if (!empty($this->config['mail']['from_item'])) {
            if (!empty($this->config['mail']['from_name_item'])) {
                $from = array(
                    $this->form_item[$this->config['mail']['from_item']]['value'],
                    $this->form_item[$this->config['mail']['from_name_item']]['value']
                );
            } else {
                $from = $this->form_item[$this->config['mail']['from_item']]['value'];
            }
        }
        $subject = $this->config['mail']['subject'];
        // 本文取得
        $body = file_get_contents(DATA_ROOT . $this->config['mail']['body_file']);

        // サブジェクト置換
        $subject = $this->replace_subject($subject);
        // メール本文置換
        $body = $this->replace_body($body);

        $mail = new Qdmail();
        $charset = (strlen($this->config['mail']['charset']) > 0) ? $this->config['mail']['charset'] : self::MAIL_CHARSET;
        $encoding = (strlen($this->config['mail']['encoding']) > 0) ? $this->config['mail']['encoding'] : self::MAIL_ENCODING;
        $mail->charset($charset, $encoding);
        
        // エラー表示制御
        $mail->error_display = $this->config['global']['error_display'];

        $mail->to($to);
        $mail->subject($subject);
        $mail->from($from);
        $mail->text($body);
        // 1件毎に送信
        $mail->toSeparate(true);

        // smtpセクションがあればSMTPサーバーを経由して送信する
        if (!empty($this->config['smtp'])) {
            $param = array();
            switch ($this->config['smtp']['protocol']) {
                case 'POP_BEFORE':
                    $param['pop_host'] = $this->config['smtp']['pop_host'];
                case 'SMTP_AUTH':
                    $param['user'] = $this->config['smtp']['user'];
                    $param['pass'] = $this->config['smtp']['password'];
                default:
                    $param['host'] = $this->config['smtp']['host'];
                    $param['port'] = $this->config['smtp']['port'];
                    $param['from'] = $this->config['mail']['from'];
                    $param['protocol'] = $this->config['smtp']['protocol'];
                    break;
            }
            $mail->smtp(true);
            $mail->smtpServer($param);
        }

        // ログ
        if ($this->config['global']['log_level']) {
            $mail->logLevel($this->config['global']['log_level']);
            $mail->logPath(DATA_ROOT . self::MAIL_LOG_DIR);
            $mail->logFilename(self::MAIL_LOG_FILENAME);
        }
        // エラーログ
        if ($this->config['global']['error_log_level']) {
            $mail->errorlogLevel($this->config['global']['error_log_level']);
            $mail->errorlogPath(DATA_ROOT . self::MAIL_LOG_DIR);
            $mail->errorlogFilename(self::MAIL_ERROR_LOG_FILENAME);
        }

        // メール送信
        if (!$mail->send()) {
            $this->error_screen($this->config['message']['send']);
        }

        // 自動応答メール処理
        if (!empty($this->config['mail']['responder_to'])) {
            $responder_to = $this->config['mail']['responder_to'];
            $to = $this->form_item[$responder_to]['value'];
            if (!empty($this->config['mail']['responder_from'])) {
                if (!empty($this->config['mail']['responder_from_name'])) {
                    $from = array($this->config['mail']['responder_from'], $this->config['mail']['responder_from_name']);
                } else {
                    $from = $this->config['mail']['responder_from'];
                }
                $mail->from($from);
            }
            if (!empty($this->config['mail']['responder_subject'])) {
                $subject = $this->config['mail']['responder_subject'];
                // サブジェクト置換
                $subject = $this->replace_subject($subject);
            }
            if (!empty($this->config['mail']['responder_body_file'])) {
                $body = file_get_contents(DATA_ROOT . $this->config['mail']['responder_body_file']);

                // メール本文置換
                $body = $this->replace_body($body);
            }
            if (strlen($to) > 0) {
                $mail->to($to);
                $mail->subject($subject);
                $mail->text($body);
                // メール送信
                if (!$mail->send()) {
                    $this->error_screen($this->config['message']['send']);
                }
            }
        }

        // CAPTCHAのキーを削除
        unset($_SESSION['captcha_keystring']);

        if (strlen($this->config['flow']['redirect']) > 0) {
            // リダイレクト処理
            header('Location: ' . $this->config['flow']['redirect']);
            exit;
        } else {
            // テンプレートを取得
            $html = $this->get_template($this->config['step'][$this->step]);

            // HTML書き出し
            $this->render($html);
        }
    }

    /**
     * エラー画面
     * @param string $message エラーメッセージ
     */
    private function error_screen($message)
    {
        // テンプレートを取得
        $template = $this->get_template($this->config['step']['error']);
        $html = str_get_html($template, true, true, self::SYSTEM_CHAR_CODE, false);
        $html->find('#' . $this->config['message']['error_message_id'], 0)->outertext = $message;

        // HTML書き出し
        $this->render($html);

        // 処理終了
        die;
    }

    /**
     * テンプレート取得
     * @param string $template_path
     */
    private function get_template($template_path)
    {
        // テンプレート内容
        $template = '';
        
        if (pathinfo($template_path, PATHINFO_EXTENSION) === 'php') {
            // 拡張子がphpであれば、includeしてPHPとして評価する。

            // バッファリング制御
            ob_start();
            include($template_path);
            $template = ob_get_contents();
            //バッファを削除
            ob_end_clean();
        } else {
            // php でなければ、そのまま読み込む
            $template = file_get_contents($template_path);
        }
        // 文字コード変換
        if ($this->convert_char_code) {
            $template = mb_convert_encoding($template, self::SYSTEM_CHAR_CODE, $this->config['global']['char_code']);
        }
        
        return $template;
    }
    
    
    /**
     * HTML書き出し
     * @param object $html simple_html_dom
     */
    private function render($html)
    {
        if ($this->convert_char_code) {
            $html = mb_convert_encoding($html, $this->config['global']['char_code'], self::SYSTEM_CHAR_CODE);
        }

        // HTML書き出し
        echo $html;
    }

    /**
     * 件名を入力値と置換する
     * @param string $subject
     * @return string $subject
     */
    private function replace_subject($subject)
    {
        // 入力値と置換
        foreach ($this->form_item as $name => $item) {
            switch ($item['type']) {
                case 'text':
                case 'radio':
                case 'hidden':
                case 'email':
                    $subject = str_replace('{' . $name . '}', $item['value'], $subject);
                    break;
                case 'checkbox':
                    if (is_array($item['value'])) {
                        $subject = str_replace('{' . $name . '}', implode($this->config['checkbox']['delimiter'], $item['value']), $subject);
                    } else {
                        $subject = str_replace('{' . $name . '}', $item['value'], $subject);
                    }
                    break;
                case 'select':
                    if (is_array($item['value'])) {
                        $subject = str_replace('{' . $name . '}', implode($this->config['select']['delimiter'], $item['value']), $subject);
                    } else {
                        $subject = str_replace('{' . $name . '}', $item['value'], $subject);
                    }
                    break;
                default:
                    break;
            }
        }

        return $subject;
    }

    /**
     * メール本文に入力値を置換する
     * @param string $body
     * @return string $body
     */
    private function replace_body($body)
    {
        // 日付フォーマット
        $date = date($this->config['mail']['date_format']);
        // 本文内を入力値に置換
        foreach ($this->form_item as $name => $item) {
            if ($item['type'] === 'checkbox' && is_array($item['value'])) {
                $body = str_replace('{' . $name . '}', implode($this->config['checkbox']['delimiter'], $item['value']), $body);
            } else if ($item['type'] === 'select' && is_array($item['value'])) {
                $body = str_replace('{' . $name . '}', implode($this->config['select']['delimiter'], $item['value']), $body);
            } else {
                $body = str_replace('{' . $name . '}', $item['value'], $body);
            }
        }
        // 送信情報
        $body = str_replace('{_date}', $date, $body);
        $body = str_replace('{_ip}', $_SERVER['REMOTE_ADDR'], $body);
        $body = str_replace('{_host}', gethostbyaddr($_SERVER['REMOTE_ADDR']), $body);
        $body = str_replace('{_ua}', $_SERVER['HTTP_USER_AGENT'], $body);

        return $body;
    }
    
    /**
     * 文字列変換
     */
    private function convert()
    {
        foreach ($this->form_item as &$item) {
            // 全角・半角変換
            if ($item['convert_kana'] && strlen($item['value']) > 0) {
                $item['value'] = mb_convert_kana($item['value'], $item['convert_kana']);
            }
            // 大文字変換
            if ($item['convert_upper'] && strlen($item['value']) > 0) {
                $item['value'] = mb_strtoupper($item['value']);
            }
            // 大文字変換
            if ($item['convert_lower'] && strlen($item['value']) > 0) {
                $item['value'] = mb_strtolower($item['value']);
            }
        }
        
    }

    /**
     * 入力値チェック
     */
    private function validate()
    {
        // リファラーチェック
        if ($this->config['security']['referer'] && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            $this->error_screen($this->config['message']['referer']);
        }

        // validation
        foreach ($this->form_item as &$item) {
            // メールアドレス書式簡易チェック
            if ($item['type'] === 'email' && strlen($item['value']) > 0
                && !preg_match('/^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i', $item['value'])) {
                $item['error'] = $this->config['message']['email'];
                $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                $this->is_error = true;
                continue;
            }
            // 最大文字数チェック
            if ($item['maxlength'] && mb_strlen($item['value']) > $item['maxlength']) {
                $item['error'] = str_replace('{maxlength}', $item['maxlength'], $this->config['message']['maxlength']);
                $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                $this->is_error = true;
                continue;
            }
            // 必須項目チェック
            if ($item['required'] && strlen($item['value']) === 0) {
                if ($item['type'] === 'select' || $item['type'] === 'radio') {
                    $item['error'] = $this->config['message']['required_option'];
                    $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                } else if ($item['type'] === 'checkbox') {
                    $item['error'] = $this->config['message']['required_check'];
                    $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                } else {
                    $item['error'] = $this->config['message']['required'];
                    $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                }
                $this->is_error = true;
                continue;
            }
            // 半角数字のみかどうかチェックする
            if ($item['numeric'] && strlen($item['value']) > 0 && !preg_match('/^[0-9]*$/', $item['value'])) {
                $item['error'] = $this->config['message']['numeric'];
                $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                $this->is_error = true;
                continue;
            }
            // 電話番号かどうかチェックする
            if ($item['phone'] && strlen($item['value']) > 0 && !preg_match('/^\d{2,5}-\d{1,4}-\d{4}$/', $item['value'])) {
                $item['error'] = $this->config['message']['phone'];
                $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                $this->is_error = true;
                continue;
            }
            // 郵便番号かどうかチェックする
            if ($item['postal'] && strlen($item['value']) > 0 && !preg_match('/^\d{3}-\d{4}$/', $item['value'])) {
                $item['error'] = $this->config['message']['postal'];
                $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                $this->is_error = true;
                continue;
            }
            // 入力値が同じかどうかチェックする
            if (strlen($item['equal_to']) > 0) {
                $equal_to_item = $this->form_item[$item['equal_to']];
                if ($item['value'] !== $equal_to_item['value']) {
                    $item['error'] = $this->config['message']['equal_to'];
                    $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                    $item['error'] = str_replace('{equal_to_label}', $equal_to_item['label'], $item['error']);
                    $this->is_error = true;
                    continue;
                }
            }
            // CAPTCHA
            if ($item['type'] === 'captcha') {
                if (!isset($_SESSION['captcha_keystring']) || $_SESSION['captcha_keystring'] !== $item['value']) {
                    $item['error'] = $this->config['message']['captcha'];
                    $item['error'] = str_replace('{label}', $item['label'], $item['error']);
                    $this->is_error = true;
                    continue;
                }
            }
        }

        // エラーメッセージ
        if ($this->is_error) {
            $this->step = self::ENTRY;
            $this->update_value = true;
        }
    }

    /**
     * ステップパラメータを持たせるhidden要素を返す
     * @return string
     */
    private function get_step_parameter()
    {
        return '<input type="hidden" name="' . self::STEP_PARAMETER . '" value="'
            . $this->get_next_step() . '"' . $this->self_closing_tag() . '>';
    }

    /**
     * html特殊文字をエンティティ化する
     * @param string $str エンティティ化対象文字列
     * @return string 特殊文字をエンティティ化した文字列
     */
    private function html_escape($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, self::SYSTEM_CHAR_CODE);
    }

    /**
     * html escapeしたうえで、改行をbrタグに変換する
     * @param string $str 出力する文字列
     * @return string
     */
    private function nl2br_escape($str)
    {
        if (PHP_VERSION_ID >= 50300) {
            if ($this->config['global']['xhtml']) {
                return nl2br($this->html_escape($str), true);
            } else {
                return nl2br($this->html_escape($str), false);
            }
        } else {
            return nl2br($this->html_escape($str));
        }
    }

    /**
     * xhtmlの場合は " /" を返す
     * @return string
     */
    private function self_closing_tag()
    {
        return ($this->config['global']['xhtml']) ? ' /' : '';
    }

    /**
     * magic_quotes_gpc対策
     */
    private function against_magic_quotes($arr)
    {
        return is_array($arr) ?
            array_map(array('Me_MailForm', 'against_magic_quotes'), $arr) :
            stripslashes($arr);
    }
    
    /**
     * 複数のtoアドレスを指定できる形式に変換する
     * @param string $value
     * @return array
     */
    private function multi_to_address($value)
    {
        return array(trim($value), '');
    }    
}