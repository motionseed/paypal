<?php
/**
 * 2007-2020 PrestaShop
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/afl-3.0.php
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to license@prestashop.com so we can send you a copy immediately.
 *
 *  DISCLAIMER
 *
 *  Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 *  versions in the future. If you wish to customize PrestaShop for your
 *  needs please refer to http://www.prestashop.com for more information.
 *
 *  @author 2007-2019 PayPal
 *  @author 202 ecommerce <tech@202-ecommerce.com>
 *  @copyright PayPal
 *  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace PaypalAddons\classes\API\Request;


use PaypalAddons\classes\AbstractMethodPaypal;
use PaypalAddons\classes\API\Response\Error;
use PaypalAddons\classes\API\Response\ResponseOrderRefund;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPalHttp\HttpException;
use Symfony\Component\VarDumper\VarDumper;

class PaypalOrderRefundRequest extends RequestAbstract
{
    protected $paypalOrder;

    public function __construct(PayPalHttpClient $client, AbstractMethodPaypal $method, \PaypalOrder $paypalOrder)
    {
        parent::__construct($client, $method);
        $this->paypalOrder = $paypalOrder;
    }

    public function execute()
    {
        $response = new ResponseOrderRefund();
        $captureRefund = new CapturesRefundRequest($this->paypalOrder->id_transaction);
        $captureRefund->prefer('return=representation');

        if ($body = $this->buildRequestBody()) {
            $captureRefund->body = $body;
        }

        try {
            $exec = $this->client->execute($captureRefund);

            if (in_array($exec->statusCode, [200, 201, 202])) {
                $response->setSuccess(true)
                    ->setIdTransaction($exec->result->id)
                    ->setStatus($exec->result->status)
                    ->setAmount($exec->result->amount->value)
                    ->setDateTransaction($this->getDateTransaction($exec));
            } else {
                $error = new Error();
                $resultDecoded = json_decode($exec->message);
                $error->setMessage($resultDecoded->message);
                $response->setSuccess(false)->setError($error);
            }
        } catch (HttpException $e) {
            $error = new Error();
            $resultDecoded = json_decode($e->getMessage());
            $error->setMessage($resultDecoded->details[0]->description)->setErrorCode($e->getCode());
            $response->setSuccess(false)
                ->setError($error);

            if ($resultDecoded->details[0]->issue == 'CAPTURE_FULLY_REFUNDED') {
                $response->setAlreadyRefunded(true);
            }
        } catch (\Exception $e) {
            $error = new Error();
            $error->setErrorCode($e->getCode())->setMessage($e->getMessage());
            $response->setError($error)->setSuccess(false);
        }

        return $response;
    }

    protected function getDateTransaction($exec)
    {
        $date = \DateTime::createFromFormat(\DateTime::ATOM, $exec->result->create_time);
        return $date->format('Y-m-d TH:i:s');
    }

    /**
     * @return array
     */
    protected function buildRequestBody()
    {
        return [];
    }
}