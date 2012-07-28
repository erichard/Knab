#Knab PHP Library

## What is Knab?

Knab is a small PHP Library which provides an easy connection to your
bank account.

## Supported Bank

 - La Banque Postale
 - Crédit Agricole (Deprecated)

### Deprecated backends

I don't know if the _Crédit Agricole_ backend is working or not.
I need to rewrite it with the Goutte HTTP Client instead of Zend_Http_Client but it take time and I don't really need it (as I have no account in this bank). Also, I no longer provide the Zend librairies in Knab so if you want to try it you will need to get these librairies and load tehm.

In fact, the Credit Agricol have opened an API that I may use in the future.
Tell me if you are interested.

## Documentation

 - [How To Use](https://github.com/erichard/Knab/blob/master/doc/how_to_use.md) - How to use the library

## Requirements

Knab use namespace so it will need PHP 5.3 or later.
If you still want to use PHP 5.2 note that the conversion
should not be a hard work.

## License

Knab is released under MIT license.