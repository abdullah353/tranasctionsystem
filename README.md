# TODOS:

1. 'transactions.tsv' -- tab-separated file with the following data columns:

  1a) ~~Created (Datetime),

  1b) Amount (Float ###.##),

  1c) CardholderName (Char 64),

  1d) CardNumber (Char 32),

  1e) ExpiredM (Integer ##),

  1f) ExpiredY (Integer ##),

  1g) CVV (Integer ###),

  1h) Status (Enum: 'Approved','Declined')

2. 'router.php' -- php-script to support CRUD methods for the table (1).

  Except CRUD methods the php class should provide the frot-end with hardcoded encryption key.

3. 'app.js' -- Ext JS 3 application with the following components:

  3a) grid-table with transaction's list with column sorting/filtering

  3b) 'Add New', 'Modify', 'Delete' buttons

  3c) pop-up form-panel to create or modify transaction

4. 'index.html' -- the minimum required HTML document to start the application (3)
 
Application features:

The (1) table must contain the 'CardNumber' column encrypted.

The (3a) grid-table should represent the 'CardNumber' masked (e.g. 464565XXXXXX4545)

The (3) application should send/receive all data in JSON, the all fields are plain but 'CardNumber' always encrypted.

All fields in the (3c) form are validated on the client side. The 'CardNumber' uses LUN algorithm.
When initializing the application should requests from the back-end an encryption/decryption key.
Encryption Algorithm is arbitrary.
