<?php


error_reporting(0);
header('Content-Type: text/json');
header('Charset: UTF-8');

$request = $_POST;

$merchant_id = 'YOUR MERCHANT ID';
$service_id = 'YOUR SERVICE ID';
$merchant_user_id = 'YOUR MERCHANT USER ID';
$secret_key = 'SECRET KEY';


// Проверка отправлено-ли все параметры
if (!(isset($request['click_trans_id']) &&
    isset($request['service_id']) &&
    isset($request['merchant_trans_id']) &&
    isset($request['amount']) &&
    isset($request['action']) &&
    isset($request['error']) &&
    isset($request['error_note']) &&
    isset($request['sign_time']) &&
    isset($request['sign_string']) &&
    isset($request['click_paydoc_id']))) {

    echo json_encode(array(
        'error' => -8,
        'error_note' => 'Error in request from click'
    ));

    exit;
}

// Проверка хеша
$sign_string = md5(
    $request['click_trans_id'] .
    $request['service_id'] .
    $secret_key .
    $request['merchant_trans_id'] .
    $request['merchant_prepare_id'] .
    $request['amount'] .
    $request['action'] .
    $request['sign_time']
);

// check sign string to possible
if ($sign_string != $request['sign_string']) {

    echo json_encode(array(
        'error' => -1,
        'error_note' => 'SIGN CHECK FAILED!'
    ));

    exit;
}

if ((int)$request['action'] != 1) {

    echo json_encode(array(
        'error' => -3,
        'error_note' => 'Action not found'
    ));

    exit;
}

// merchant_trans_id - это ID пользователья который он ввел в приложении
// Здесь нужно проверить если у нас в базе пользователь с таким ID

$user = get_by_id($request['merchant_trans_id']);

if (!$user) {
    echo json_encode(array(
        'error' => -5,
        'error_note' => 'User does not exist'
    ));

    exit;
}

//

$prepared = get_by_log_id($request['merchant_prepare_id']);

if (!$prepared) {
    echo json_encode(array(
        'error' => -6,
        'error_note' => 'Transaction does not exist'
    ));

    exit;
}


// Если это заказ тогда нужно проверить еще статус заказа, все еще заказ актуален или нет
// если проверка не проходит то нужно возвращать ошибку -4

// и еще нужно проверить сумму заказа
// если не проходит тогда нужно возвращать ошибку -2

// Еще одна проверка статуса заказа, не закрыть или нет
// если заказ отменен тогда нужно возвращать ошибку - 9

// Все проверки прошли успешно, тог здесь будем сохранять в базу что подготовка к оплате успешно прошла
// можно сделать отдельную таблицу чтобы сохранить входящих данных как лог
// и присвоит на параметр merchant_confirm_id номер лога
//

// Хотя все проверки выше были в prepare тоже, нужно убедится что еще раз проверить в complete

// Ошибка деньги с карты пользователя не списались
if ($request['error'] < 0) {
    // делаем что нибудь (если заказ отменим заказ, если пополнение тогда добавим запись что пополненние не успешно)
    echo json_encode(array(
        'error' => -6,
        'error_note' => 'Transaction does not exist'
    ));

    exit;
} else {
    // Все успешно прошел деньги списаны с карты пользователя тогда записываем в базу (сумма приходит в запросе)

    echo json_encode(array(
        'error' => 0,
        'error_note' => 'Success',
        'click_trans_id' => $request['click_trans_id'],
        'merchant_trans_id' => $request['merchant_trans_id'],
        'merchant_confirm_id' => $log_id,
    ));

    exit;
}
