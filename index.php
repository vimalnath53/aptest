<!DOCTYPE html>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<html>
<head>
  <style>
  #apple-pay-button {
    background-color: #3dcc6f;
    font-size: 18.5px;
    position: absolute;
    margin-top: 30px;
    width: 20%;
    border-radius: 3px;
    color: #fff;
    padding: 10px;
    cursor: pointer;
    text-align: center;
  }
  </style>
</head>
  <title>Braintree Apple Pay demo (Sandbox)</title>
  <body>
    <h1>Apple Pay Demo!</h1>

    <script src="https://js.braintreegateway.com/web/3.46.0/js/client.min.js"></script>
    <script src="https://js.braintreegateway.com/web/3.46.0/js/apple-pay.min.js"></script>


    <form action="/txn" method="post" id="form">
      <div class="form-buttons">
        <div id="apple-pay-button" style="display:none;">COMPLETE PURCHASE</div>
        <button style="display:none;" type="submit">Create transaction</button>
      </div>
    </form>


    <script>
      var applePayClicked = function (applePayInstance) {
        var paymentRequest = applePayInstance.createPaymentRequest({
            total: {
                  label: 'Braintree Demo',
                  amount: '1.00'
                },
            requiredBillingContactFields: ["postalAddress"]
        });

        paymentRequest.supportedNetworks = ['maestro', 'visa', 'amex', 'masterCard', 'discover'];

        var session = new ApplePaySession(3, paymentRequest);

        session.onvalidatemerchant = function (event) {
          applePayInstance.performValidation({
            validationURL: event.validationURL,
            displayName: 'Braintree Demo'
          }).then(function (merchantSession) {
            session.completeMerchantValidation(merchantSession);
          }).catch(function (validationErr) {
            // You should show an error to the user, e.g. 'Apple Pay failed to load.'
            console.error('Error validating merchant:', validationErr);
            session.abort();
          });
        };

        session.onpaymentauthorized = function (event) {
          console.log('Your shipping address is:', event.payment.shippingContact);

          applePayInstance.tokenize({
            token: event.payment.token
          }).then(function (payload) {
            // Send payload.nonce to your server.
            console.log('nonce:', payload.nonce);

            // If requested, address information is accessible in event.payment
            // and may also be sent to your server.
            console.log('billingPostalCode:', event.payment.billingContact.postalCode);

            var form = document.getElementsByTagName("form")[0];
            var nonceInput = document.createElement('input');
            nonceInput.name = "nonce";
            nonceInput.value = payload.nonce;

            var billingZipCodeInput = document.createElement('input');
            billingZipCodeInput.name = "billingZipCode";
            billingZipCodeInput.value = event.payment.billingContact.postalCode;

            form.appendChild(nonceInput);
            form.appendChild(billingZipCodeInput);

            // After you have transacted with the payload.nonce,
            // call `completePayment` to dismiss the Apple Pay sheet.
            session.completePayment(ApplePaySession.STATUS_SUCCESS);
          }).catch(function (tokenizeErr) {
            console.error('Error tokenizing Apple Pay:', tokenizeErr);
            session.completePayment(ApplePaySession.STATUS_FAILURE);
          });
        };

        session.begin();
      };

      var f = function() {
        console.log('Attempting to load Braintree stuff');
        if (!window.ApplePaySession) {
          console.error('This device does not support Apple Pay');
          return;
        }

        if (!ApplePaySession.canMakePayments()) {
          console.error('This device is not capable of making Apple Pay payments');
          return;
        }

        console.log("Device supports Apple Pay!");

        braintree.client.create({
          authorization: 'production_f7wnf3yf_dfy45jdj3dxkmz5m'
        }, function (clientErr, clientInstance) {
          if (clientErr) {
            console.error('Error creating client:', clientErr);
            return;
          }

          braintree.applePay.create({
            client: clientInstance
          }, function (applePayErr, applePayInstance) {
            if (applePayErr) {
              console.error('Error creating applePayInstance:', applePayErr);
              return;
            }

            ApplePaySession.canMakePaymentsWithActiveCard(applePayInstance.merchantIdentifier).then(function (canMakePayments) {
              if (!canMakePayments) {
                console.error('This device is not capable of making Apple Pay payments with an active card');
              }
            });

            button = document.getElementById("apple-pay-button");
            button.style.display = "inline";

            button.addEventListener("click", function (event) {
              applePayClicked(applePayInstance);
            });
          });
        });
      };

      f();
    </script>
  </body>
</html>

