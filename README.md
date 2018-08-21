WalletNotify
============


## Overview
bitcoind provides a configuration parameter called [*walletnotify*](https://en.bitcoin.it/wiki/Running_Bitcoin) that accepts a script as its parameter.  When the wallet receives a transaction for a local address it hits the script configured, passing the txid incoming.  

The *walletnotify* script is hit twice for each transaction; when it first appears on the network (0 confirmations) and after the first confirmation.  It is called for both receiving and sending transactions.

**walletnotify.php** is a self-contained PHP script for processing *walletnotify* calls.  Use as is or customize for your own needs.

### Features
- Processes bitcoind *walletnotify* calls and inserts the transaction into a database (MySQL or SQLite).
- Sends email and SMS notifications on transactions affecting local BTC addresses.
- Provides a simple interface for chaining other processes.


### Usage
- Receive SMS or email update anytime funds are sent or received.
- Process payments automatically whenever coins are received.
- Automatically sweep coins to cold wallets.
- Automatically pay affiliates / partners pre transaction.
- Micro-Paygate Support Tool

### Install
1. Adjust the configs as need in walletnotify.php. Making sure to add the bitcoind RPC access parameters for your local daemon.
2. Create db and tables using the SQL create statements a the top of walletnotify.php.
3. Add the following to your bitcoin.conf:

	`
walletnotify=/FULL/PATH/TO/php -f /FULL/PATH/TO/walletnotify.php %s
	`
4. TEST!!!!
5. Execute manually and pass in any recent txid.

	`
php -f walletnotify.php <txid>
	`
6. Check db to verify it was inserted.
7. If inserted correctly, Restart bitcoind.  If not, DEBUG AND TEST!!
8. Send a few satoshi to a local address to verify bitcoind is able to call the script.




