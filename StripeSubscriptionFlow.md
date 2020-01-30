## (Server )Ajax Request 1 with `payment_method_id`

Task 1
---
Create a customer with just name and email
```json
{"object":"Customer","method":"create","args":{"description":"cep.jewel@gmail.com","email":"cep.jewel@gmail.com"}}
```

Task 2
---
Create a PaymentIntent for that customer
```json
{"object":"PaymentIntent","method":"create","args":{"amount":999,"currency":"USD","setup_future_usage":"off_session","confirmation_method":"manual","save_payment_method":true,"description":"Recurring Test","statement_descriptor":"Recurring Test","confirm":true,"payment_method":"pm_1G69JAAybSf0xwaPKhtxXXU8","customer":"cus_GdQ9r0tOBcXRih","metadata":{"email":"cep.jewel@gmail.com"},"capture_method":"manual"}}
```

Task 3
--
Update the customer now
```json
{"object":"Customer","method":"update","args":"cus_GdQMw9QOxaRKzO","all_args":["Customer","update","cus_GdQMw9QOxaRKzO",{"invoice_settings":{"default_payment_method":"pm_1G69VqAybSf0xwaPdwTdlEOC"}}]}
```

Finally, Return the intent stripe object to client side.


Test Card:
---
SCA: `4000002760003184`
