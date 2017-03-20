<?php
use hyperjiang\BankCard;

require __DIR__ . '/../vendor/autoload.php';

class TestBankCard extends PHPUnit_Framework_TestCase
{
    public function testBankCardInfo()
    {
        var_dump(BankCard::info('6230943240001751067'));
        $this->assertEquals([
            'bankCode'     => 'CEB',
            'bankName'     => '中国光大银行',
            'cardType'     => 'CC',
            'cardTypeName' => '信用卡',
        ], BankCard::info('6225700000000000'));

        $this->assertEquals([
            'bankCode'     => 'SPDB',
            'bankName'     => '上海浦东发展银行',
            'cardType'     => 'DC',
            'cardTypeName' => '储蓄卡',
        ], BankCard::info('6217921400000000'));

        $this->assertEquals([
        ], BankCard::info('4402905009100000'));

        $this->assertEquals('https://apimg.alipay.com/combo.png?d=cashier&t=ABC', BankCard::getBankIcon('ABC'));
    }

    public function testCheck()
    {
        $this->assertEquals(true, BankCard::checkCardNo('6223200724780220'));
    }

    public function testCheckBank()
    {
        $handle = fopen("./接口识别处数据详情.txt", 'r');
        while ($row = fgets($handle)) {
            $row     = explode('|', $row);
            $bankRow = $row[0];
            if (strlen($bankRow) == 16 || strlen($bankRow) == 19) {
                if (!BankCard::checkCardNo($bankRow)) {
                    file_put_contents('test.abc', $bankRow . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }
}
