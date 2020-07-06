<?php
require_once realpath(__DIR__) . '/../../../autoload.php';
use Prinx\Utils\Arr;
// use Prinx\Utils\Date;
// \set_time_limit(180);
// $res = PayswitchMomoService::pay('233545466796', '01', '0.2');
/*
Error 1: voucher code in JSON
Error 2: {"status":"Declined","
code":"030",
"reason":"Transaction amount below GHS 0.10 are not allowed.","auth_code":"030"}"
}

Error 3: {
["SUCCESS"]=> bool(false)
["data"]=> bool(false)
["error"]=> string(66) "Operation timed out after 30001 milliseconds with 0 bytes received"
}

{
"status": "declined",
"code": 109,
"reason": "Transaction timed out or declined",
"transaction_id": "000248975873"
}
 */
// echo base64_encode('nampa5e3995012a9fd:MTAyZWEyYmIyYmU0YmY1ZTI4NDkyNjAwMTRiMzZhMzM=');

// echo str_pad(rand(1, 999999999), 12, '0', STR_PAD_LEFT);

// echo str_pad(floatval(0.2) * 100, 12, '0', STR_PAD_LEFT);
// var_dump($res);

// function isDate($date, $format = 'd/m/Y')
// {
//     $v = new \stdClass;
//     $v->validated = true;

//     if (!Date::isDate($date, $format)) {
//         $v->validated = false;
//         $v->error = 'Invalid date.';
//     }

//     return $v;
// }

// var_dump(preg_replace('/($0|\/0)/', '', '02/7/2020'));

// var_dump(isDate('27/2020', 'j/n/Y'));

// var_dump(DateTime::createFromFormat('j/n/Y', '02/7/2020'));

// echo "<br>";
// echo "{isDate('27/2020', 'j/n/Y')}";

// var_dump(json_decode('{
//     "MTN": {
//         "mnc": "01",
//         "patterns": [
//             "((\\+?233\\(0\\)|\\+?233|0))?24[0-9]{7,10}",
//             "((\\+?233\\(0\\)|\\+?233|0))?54[0-9]{7,10}",
//             "((\\+?233\\(0\\)|\\+?233|0))?55[0-9]{7,10}",
//             "((\\+?233\\(0\\)|\\+?233|0))?59[0-9]{7,10}"
//         ],
//         "test_phones": {
//             "233204038261": {
//                 "name": "Mike MTN"
//             },
//             "233242245046": {
//                 "name": "Razak MTN"
//             },
//             "233544316046": {
//                 "name": "TXTConnect MTN"
//             },
//             "233545466796": {
//                 "name": "Prince MTN"
//             },
//             "233549143481": {
//                 "name": "Test Phone 2"
//             }
//         }
//     }
// }'));
// ob_start();
// echo 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';

// function fun()
// {
//     echo 'nbnvc';
//     echo ob_get_contents();

//     header('Content-Encoding: none');

//     if (error_get_last() === null) {
//         header('Content-Length: ' . ob_get_length());
//     }

//     header('Connection: close');
//     ob_end_flush();
//     ob_flush();
//     flush();
// }
// fun();
// // 95.388089179993
// $a = 0;
// $time = microtime(true);
// while ($a <= 10000000000) {
//     $a++;
// }
// $finish = microtime(true) - $time;

// file_put_contents('test.json', $finish);
// var_dump($value = [
//     'rules' => 'fffff',
//     'je sais',
// ]);

// echo $value['error'] ?? $value[0] ?? 1;

$arr = [
    'fffff' => [
        'dd' => [
            'cc' => 'llllll',
            'kkkkkk' => 'vvvvv',
        ],
        'gggg' => [
            'aaaa' => 'ooooooo',
        ],
    ],
];

function fromArr($key)
{
    global $arr;
    $lookup = $arr;
    $exploded = explode('.', $key);

    foreach ($exploded as $value) {
        if (!is_array($lookup) || !isset($lookup[$value])) {
            return null;
        }

        $lookup = $lookup[$value];
    }

    return $lookup;
}

// var_dump(fromArr('fffff.dd.cc.llllll'));

function addToArr($key, $value, $array, $sep = '.', $remove = false)
{
    $exploded = explode($sep, $key);
    $depth = count($exploded);

    if ($remove) {
        $toAdd = [];
    } else {
        $toAdd = [
            $exploded[$depth - 1] => $value,
        ];
    }

    for ($i = $depth - 2; $i >= 0; --$i) {
        $toAdd = [
            $exploded[$i] => $toAdd,
        ];
    }

    return array_replace_recursive($array, $toAdd);
}

header('Content-Type: application/json');
// echo json_encode($arr = addToArr('fffff.dd.cc.kkkkkk', null, $arr, '.', true));

// echo json_encode($arr = addToArr('ffffhf.dd.cc.bbbb', 'nnnnnnnnnn', $arr));
/* echo */json_encode(Arr::multiKeyRemove('fffff.dd.cc', $arr));
