<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Cart\Services\CartService;
use App\Modules\Checkout\Services\CheckoutService;
use App\Modules\Customer\Services\AuthService;
use App\Modules\Cms\Services\CmsPageService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Shipping\Services\CheckoutTotalsService;
use App\Modules\Shipping\Services\ShippingService;
use Throwable;

final class CheckoutController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
        private readonly OrderService $orders,
        private readonly ShippingService $shipping,
        private readonly CheckoutTotalsService $totals,
        private readonly CmsPageService $pages,
        private readonly PaymentService $payments,
        private readonly AuthService $auth
    ) {
    }

    public function form(): Response
    {
        $cartData = $this->carts->getCartBySession($this->sessionId());
        $shippingMethods = $this->shipping->listActiveForCheckout();
        $selectedCode = trim((string) ($_GET['shipping_method_code'] ?? ($shippingMethods[0]['code'] ?? '')));

        $selectedMethod = null;
        foreach ($shippingMethods as $method) {
            if ((string) $method['code'] === $selectedCode) {
                $selectedMethod = $method;
                break;
            }
        }
        if ($selectedMethod === null && $shippingMethods !== []) {
            $selectedMethod = $shippingMethods[0];
            $selectedCode = (string) $selectedMethod['code'];
        }

        $totalsPreview = $this->totals->calculate(
            (float) ($cartData['subtotal_amount'] ?? 0),
            (float) ($selectedMethod['price_inc_vat'] ?? 0),
            (float) ($cartData['discount_amount_inc_vat'] ?? 0)
        );

        return new Response($this->views->render('storefront.checkout', [
            'cartData' => $cartData,
            'error' => trim((string) ($_GET['error'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
            'paymentMethodOptions' => $this->checkout->paymentMethodOptions(),
            'shippingMethods' => $shippingMethods,
            'selectedShippingMethodCode' => $selectedCode,
            'totalsPreview' => $totalsPreview,
            'customer' => $this->auth->currentCustomer(),
        ]));
    }

    public function placeOrder(): Response
    {
        try {
            $checkoutData = $this->checkout->normalize($_POST);
            $checkoutData['customer_user_id'] = $this->auth->currentUserId();
            $this->carts->ensureCartItemsPurchasable($this->sessionId());
            $cartData = $this->carts->getCartBySession($this->sessionId());
            $orderNumber = $this->orders->createFromCart($checkoutData, $cartData);
            $_SESSION['last_order_number'] = $orderNumber;

            if ($this->payments->isProviderPaymentMethod((string) ($checkoutData['payment_method'] ?? ''))) {
                $orderId = $this->orders->findOrderIdByNumber($orderNumber);
                if ($orderId === null) {
                    throw new \RuntimeException('Ordern kunde inte laddas för betalning.');
                }

                $session = $this->payments->initiateProviderPayment($orderId);
                $this->carts->clearBySession($this->sessionId());

                return $this->redirect($session['redirect_url']);
            }

            $this->carts->clearBySession($this->sessionId());

            return $this->redirect('/checkout/confirmation');
        } catch (Throwable $e) {
            $shippingMethodCode = trim((string) ($_POST['shipping_method_code'] ?? ''));
            $query = '/checkout?error=' . urlencode($e->getMessage());
            if ($shippingMethodCode !== '') {
                $query .= '&shipping_method_code=' . urlencode($shippingMethodCode);
            }

            return $this->redirect($query);
        }
    }

    public function confirmation(): Response
    {
        $orderNumber = $_SESSION['last_order_number'] ?? null;
        $publicOrder = is_string($orderNumber) ? $this->orders->getPublicOrderSummaryByNumber($orderNumber) : null;

        return new Response($this->views->render('storefront.order_confirmation', [
            'orderNumber' => is_string($orderNumber) ? $orderNumber : null,
            'publicOrder' => $publicOrder,
            'paymentMethodLabel' => $this->orders->paymentMethodLabel((string) ($publicOrder['payment_method'] ?? '')),
            'paymentNextStepText' => $this->orders->paymentNextStepText((string) ($publicOrder['payment_method'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    public function orderStatus(): Response
    {
        $orderNumber = trim((string) ($_GET['order_number'] ?? ''));
        $summary = $orderNumber !== '' ? $this->orders->getPublicOrderSummaryByNumber($orderNumber) : null;

        return new Response($this->views->render('storefront.order_status', [
            'queryOrderNumber' => $orderNumber,
            'orderSummary' => $summary,
            'paymentMethodLabel' => $this->orders->paymentMethodLabel((string) ($summary['payment_method'] ?? '')),
            'paymentNextStepText' => $this->orders->paymentNextStepText((string) ($summary['payment_method'] ?? '')),
            'showNotFound' => $orderNumber !== '' && $summary === null,
            'paymentResult' => trim((string) ($_GET['payment_result'] ?? '')),
            'paymentMessage' => trim((string) ($_GET['payment_message'] ?? '')),
            'infoPages' => $this->pages->storefrontInfoPages(),
        ]));
    }

    private function sessionId(): string
    {
        return session_id();
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
