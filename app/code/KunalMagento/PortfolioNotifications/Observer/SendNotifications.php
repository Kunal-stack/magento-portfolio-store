<?php
declare(strict_types=1);

namespace KunalMagento\PortfolioNotifications\Observer;

use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class SendNotifications implements ObserverInterface
{
    public function __construct(
        private TransportBuilder $transportBuilder,
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        private InvoiceService $invoiceService,
        private Transaction $transaction,
        private InvoiceSender $invoiceSender,
        private OrderSender $orderSender,
        private LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        $storeId = (int)$order->getStoreId();

        // Log that we fired (for verification)
        $this->logger->info(sprintf('[PortfolioNotifications] Handling order %s', $order->getIncrementId()));

        // --- A) Send Magento’s native "new order" email to customer ---
        try {
            $this->orderSender->send($order, true); // second arg: force send even if config disabled
            $this->logger->info('[PortfolioNotifications] Order email sent');
        } catch (\Throwable $e) {
            $this->logger->error('[PortfolioNotifications] Order email failed: ' . $e->getMessage());
        }

        // --- B) Email to ME (admin notification) ---
        try {
            $me = (string)$this->scopeConfig->getValue(
                'kunalmagento_portfolio/emails/recipient',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($me) {
                $from = ['name' => 'Kunal Portfolio', 'email' => 'no-reply@example.local'];
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier('kunal_portfolio_order_to_me')
                    ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                    ->setTemplateVars(['order' => $order])
                    ->setFromByScope($from, $storeId)
                    ->addTo($me)
                    ->getTransport();
                $transport->sendMessage();
                $this->logger->info('[PortfolioNotifications] Admin email sent');
            } else {
                $this->logger->info('[PortfolioNotifications] Admin email skipped (no recipient configured)');
            }
        } catch (\Throwable $e) {
            $this->logger->error('[PortfolioNotifications] Admin email failed: ' . $e->getMessage());
        }

        // --- C) Try to create OFFLINE invoice & email it (optional extra) ---
        try {
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                if ($invoice && $invoice->getTotalQty()) {
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);
                    $this->transaction->addObject($invoice)->addObject($invoice->getOrder())->save();
                    $this->invoiceSender->send($invoice);
                    $this->logger->info('[PortfolioNotifications] Invoice created & email sent');
                }
            } else {
                $this->logger->info(sprintf('[PortfolioNotifications] Skipped invoice for order %s (canInvoice=false)', $order->getIncrementId()));
            }
        } catch (\Throwable $e) {
            $this->logger->error('[PortfolioNotifications] Invoice/email failed: ' . $e->getMessage());
        }
    }
}
