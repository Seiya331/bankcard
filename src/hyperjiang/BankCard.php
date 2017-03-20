<?php

namespace hyperjiang;

use GuzzleHttp\Client;

class BankCard
{
    /**
     * 获取支付宝提供的银行图标
     *
     * @param  string $bankCode 银行代码
     * @return string
     */
    public static function getBankIcon($bankCode)
    {
        return "https://apimg.alipay.com/combo.png?d=cashier&t={$bankCode}";
    }

    /**
     * 根据银行卡号获取卡信息
     *
     * @param  string $cardNo 银行卡号
     * @return array
     */
    public static function info($cardNo)
    {
        $bankInfo = \hyperjiang\BankCard\Lists::getInfo($cardNo);
        if (empty($bankInfo)) {
            try {
                $bankInfo = self::alipay($cardNo);
                if (!empty($bankInfo)) {
                    $dir = __DIR__ . '/../../data/error_card.txt';
                    file_put_contents($dir, json_encode($bankInfo) . PHP_EOL, FILE_APPEND);
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        return $bankInfo;
    }

    /**
     * 通过支付宝接口获取卡信息
     *
     * @param  string $cardNo 银行卡号
     * @return array
     */
    public static function alipay($cardNo)
    {
        $url      =
            "https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo={$cardNo}&cardBinCheck=true";
        $client   = new Client(['timeout' => 2]);
        $response = $client->get($url);
        $result   = $response->getBody()->getContents();
        $result   = json_decode($result);
        $bankInfo = [];
        if ($result->validated) {
            $bankInfo = [
                'bankCode'     => $result->bank,
                'bankName'     => self::getBankName($result->bank),
                'cardType'     => $result->cardType,
                'cardTypeName' => self::getCardTypeName($result->cardType),
            ];
        }
        return $bankInfo;
    }

    /**
     * 根据银行代码获取银行名称
     *
     * @param  string $bankCode 银行代码
     * @return string
     */
    public static function getBankName($bankCode)
    {
        return \hyperjiang\BankCard\Lists::getBankName($bankCode);
    }

    /**
     * 获取卡类型名称
     *
     * @param  string $cardType 卡类型
     * @return string
     */
    public static function getCardTypeName($cardType)
    {
        return \hyperjiang\BankCard\Types::get($cardType);
    }

    public static function createCardNo($cardNo)
    {
        $len = strlen($cardNo);
        if ($len < 2) {
            throw new \Exception('the length of the string should be bigger than 2');
        }
        $sum      = 0;
        $position = 2;
        for ($index = $len - 2; $index >= 0; $index--) {
            if ($position % 2 == 0) {
                $num = $cardNo[$index] * 2;
                if ($num > 9) {
                    $num -= 9;
                }
            } else {
                $num = $cardNo[$index];
            }
            $sum += $num;
            $position++;
        }
        $result = 10 - $sum % 10;
        return substr($cardNo, 0, $len - 1) . $result;
    }

    /*
    16-19 位卡号校验位采用 Luhm 校验方法计算：
    1，将未带校验位的 15 位卡号从右依次编号 1 到 15，位于奇数位号上的数字乘以 2
    2，将奇位乘积的个十位全部相加，再加上所有偶数位上的数字
    3，将加法和加上校验位能被 10 整除。
    */
    public static function checkCardNo($cardNo)
    {
        if (empty($cardNo)) {
            return false;
        }
        $arrNo  = str_split($cardNo);
        $last_n = $arrNo[count($arrNo) - 1];
        krsort($arrNo);
        $i     = 1;
        $total = 0;
        foreach ($arrNo as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $x = 10 - ($total % 10);
        if ($x == $last_n) {
            return true;
        }
        return false;
    }
}

