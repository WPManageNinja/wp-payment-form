<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
use WPPayForm\Classes\Models\SubscriptionTransaction;
use WPPayForm\Classes\Models\Transaction;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PayMent Success Handler.
 *
 */
class PaymentSuccessHandler
{
    /*
     * This method will be called if stripe hosted checkout
     * has subscription payment
    */
    public function processSubscriptionsSuccess($subscriptions, $invoice, $submission)
    {
        $subscriptionModel = new Subscription();
        $subscriptionTransactionModel = new SubscriptionTransaction();

        foreach ($subscriptions as $subscription) {
            $subscriptionStatus = 'active';
            if ($subscription->trial_days) {
                $subscriptionStatus = 'trialling';
            }

            $updateData = [
                'status'             => $subscriptionStatus,
                'vendor_customer_id' => $invoice->customer,
                'vendor_response'    => maybe_serialize($invoice),
            ];

            if (!$subscription->vendor_subscriptipn_id) {
                $updateData['vendor_subscriptipn_id'] = $invoice->subscription;
            }

            $subscriptionModel->update($subscription->id, $updateData);

            if ($subscriptionStatus == 'trialling') {
                continue;
            }

            $totalAmount = $subscription->initial_amount + $subscription->recurring_amount;
            // We have to calculate the payment total

            $transactionItem = [
                'form_id'          => $submission->form_id,
                'user_id'          => $submission->user_id,
                'submission_id'    => $submission->id,
                'subscription_id'  => $subscription->id,
                'transaction_type' => 'subscription',
                'payment_method'   => 'stripe',
                'charge_id'        => $invoice->charge,
                'payment_total'    => $totalAmount,
                'status'           => $invoice->status,
                'currency'         => $invoice->currency,
                'payment_mode'     => ($invoice->livemode) ? 'live' : 'test',
                'payment_note'     => maybe_serialize($invoice),
                'created_at'       => date('Y-m-d H:i:s', $invoice->created),
                'updated_at'       => date('Y-m-d H:i:s', $invoice->created)
            ];
            $subscriptionTransactionModel->maybeInsertCharge($transactionItem);
        }

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe recurring subscription successfully initiated', 'wppayform')
        ));

        if (!empty($invoice->payment_intent->charges->data[0])) {
            $charge = $invoice->payment_intent->charges->data[0];
            $this->recoredStripeBillingAddress($charge, $submission);
        }
    }

    /*
     * This method will be called for
     * one time payment only or
     * one time payment with a subscription payment
     */
    public function processOnetimeSuccess($transaction, $invoice, $submission)
    {
        $updateDate = [
            'status' => 'paid'
        ];
        if (!empty($invoice->payment_intent)) {
            // This is mostly for only one time payment. If subscription payment exists
            // Then we will not get charge and payment itent which is annoying
            if (!empty($invoice->payment_intent->charges->data[0])) {
                $charge = $invoice->payment_intent->charges->data[0];
                $updateDate['charge_id'] = $charge->id;

                if (!empty($charge->payment_method_details->card)) {
                    $card = $charge->payment_method_details->card;
                    $updateDate['card_brand'] = $card->brand;
                    $updateDate['card_last_4'] = $card->last4;
                }
                if (!empty($charge->billing_details->address)) {
                    $this->recoredStripeBillingAddress($charge, $submission);
                }
            } else {
                $updateDate['charge_id'] = $invoice->payment_intent->id;
                $updateDate['created_at'] = gmdate('Y-m-d H:i:s', $invoice->payment_intent->created);
            }
        } else if (!empty($invoice->charge)) {
            $updateDate['charge_id'] = $invoice->charge;
            $updateDate['created_at'] = gmdate('Y-m-d H:i:s', $invoice->created);
        }

        $updateDate['payment_mode'] = ($invoice->livemode) ? 'live' : 'test';

        $transactionModel = new Transaction();
        $transactionModel->update($transaction->id, $updateDate);

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe One time payment has marked as paid.', 'wppayform')
        ));
    }

    private function recoredStripeBillingAddress($charge, $submission)
    {
        $formDataFormatted = $submission->form_data_formatted;
        if (isset($formDataFormatted['__checkout_billing_address_details'])) {
            return;
        }

        if (empty($charge->billing_details)) {
            return;
        }

        $billingDetails = $charge->billing_details;
        if (!empty($billingDetails->address)) {
            $formDataFormatted['__checkout_billing_address_details'] = $billingDetails->address;
        }
        if (!empty($billingDetails->phone)) {
            $formDataFormatted['__stripe_phone'] = $billingDetails->phone;
        }
        if (!empty($billingDetails->name)) {
            $formDataFormatted['__stripe_name'] = $billingDetails->name;
        }
        if (!empty($billingDetails->email)) {
            $formDataFormatted['__stripe_email'] = $billingDetails->email;
        }

        $submissionUpdateData = [
            'form_data_formatted' => maybe_serialize($formDataFormatted)
        ];
        if (!$submission->customer_name && $billingDetails->name) {
            $submissionUpdateData['customer_name'] = $billingDetails->name;
        }
        if (!$submission->customer_email && $billingDetails->email) {
            $submissionUpdateData['customer_email'] = $billingDetails->email;
        }
        $submissionModel = new Submission();
        $submissionModel->update($submission->id, $submissionUpdateData);

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Billing address from stripe has been logged in the submission data', 'wppayform')
        ));
    }
}