Sephpa - Change Log
===============

## 3.0.2 - Apr 14, '25
- Fixed the generation of error messages during `addPayment` calls (#45).

## 3.0.1 - Feb 12, '25
- `generateXml()` is now public (#42). However, it is recommended to use `generateOutput()`.
- Updated SepaUtilities.

## 3.0.0 - Jan 20, '25
- Added support for pain.001.001.09 and pain.008.001.08 (#43)
  - See the wiki for the new format of `pstlAdr`
- Support up to PHP 8.4 and drop support for PHP 8.0 and older
- Updated SepaUtilities to 2.0.0
- Updated SepaDocumentor to 3.0.0
- Fixed error reporting on failed input sanitation
- Fixed documentation on `addPayment()` (#44)

## 2.1.1 - Mar 4, '21
- Updated SepaUtilities to 1.3.3

## 2.1.0 - Mar 4, '21
- **changed**: renamed property key `dbtrPstlAdr` to `pstlAdr` for simplicity. 
  The old key still works for backward compatibility, but is deprecated from now on.
- **changed/fixed**: There was a documentation/consistency issue about some optional keys in direct 
  debit files, namely:
  - `orgnlCdtrSchmeId_Nm` -> `orgnlCdtrSchmeId_nm`
  - `orgnlCdtrSchmeId_Id` -> `orgnlCdtrSchmeId_id`
  Now the code matches the documentation. This is why this change is not considered breaking
  any existing code and rather fix it. Another documentation issue effects `orgnlDbtrAgt`. In 
  pain.008.001.02 this cannot be used, but it was checked and was mentioned in the documentation.
  This should not be confused with 008.001.02.austrian.003 where `orgnlDbtrAgt` can be used.
  In pain.008.001.02 `orgnlDbtrAgt_bic` is a valid keyword that was previously not documented.
- `pstlAdr` is now supported for all credit transfer and directed debit versions on both the 
  debtor and the creditor.
- Updated SepaUtilities to 1.3.2.

## 2.0.0 - Feb 14, '21
This is a new major release. It comes with many new features and changes. This also effects the
interface (highlighted with **bold** text). The changes are as minimal as possible to make it as easy as possible to migrate to
the new version, but still old code will not work with this version. To be clear: You should migrate
to the new version, since version 1 is no longer supported.

- new: support for PHP 7.2, 7.3, 7.4, 8.0.
- new: support for pain.001.001.03 and pain.008.001.02<br>
Notice that this implementation fits the new german standard that is valid from November 2016. 
It is said that this is compatible with the formats with the same name from 2009 that is used 
in many other countries but germany. But since it has fewer restrictions than the file format 
from 2009, you can choose witch one you need via the SepaUtilities constants `SEPA_PAIN_001_001_03`
for the old version and `SEPA_PAIN_001_001_03_GBIC` for the new german version of SEPA credit 
transfer files (respectively `SEPA_PAIN_001_001_03` and `SEPA_PAIN_001_001_03_GBIC` for direct 
debit files).
- new: support for pain.008.001.02.austrian.003
- new: an autoloader file that can be used out of the box if you don't want to use composer.
- new: SephpaMultiFile class to easier handle multiple Sephpa files.
- new: all store/download functions have now the option to store documentation files as PDF. To
use this you have to also install the package `SepaDocumentor`. See the readme for
an example.  
It also supports to download multiple files as a single zip file.
- new: `orgId > BICOrBEI` and `orgId > Othr > Id` are supported on the file level. This is needed in some countries.  
It is *highly recommended not to use it* unless your bank requires this, and you know what you 
are doing. 
- new: `InitgPtyId` is supported on the file level. This is also needed in some countries.  
It is *highly recommended not to use it* unless your bank requires this, and you know what you 
are doing.
- new: `Ctry` and `AdrLine` is supported in credit transfer files to specify debtor address.
  To make this more compact, both can be handed over an array with the keys `ctry` and/or `adrLine` to 
  the option `dbtrPstlAdr`. Have a look in the documentation for details.
- fixed: invalid xml file if checkAndSanitize is turned off and `AmdmntInd` is not provided (issue #6)
- fixed: some minor bugs no one seems to have noticed yet.
- **fixed**: There was a typo in the parameter `ultmtDbtr` (was `ultmatDebtr` before). Make sure
  to adjust your code if you used this parameter.
- changed: updated SepaUtilities to ~1.3.0
- changed: made generateXml private. This should not break any code, since no one should be using
- changed: corrected some doc comments
this function directly. It was only public to directly access the generated xml for testing.
- **changed**: renamed `storeSepaFile()` to `store()` and `downloadSepaFile()` to `download()`.
- **changed**: removed the creation date parameter from `storeSepaFile()` and `downloadSepaFile()`.
- **changed**: the file name provided to `storeSepaFile()` and `downloadSepaFile()` should no longer
contain a file ending like `.xml`.
This should not break any code since it was recommended not to use this. It was just for easier testing.
- **changed**: Sephpa constructor now throws a SephpaInvalidInput exception if the input was invalid
and couldn't be sanitized.
- dependency: For multi file downloads you need [`libzip`](http://php.net/manual/en/book.zip.php).
- dev: improved testing of SEPA files and added a ton of tests.
- dev: updated PHPUnit to v8 and v9 depending on PHP version.

## 1.3.0 - Feb 5, '15
- updated SepaUtilities to 1.1.0
- changed licence to GNU LGPL v3.0

## 1.2.4 - Jan 27, '15
- fixed a bug that results in invalid sepa file, if BIC is not provided for credit transfer (pain.001.003.03)

## 1.2.3 - Dec 18, '14
- bugfix: removed the `require` in Sephpa.php. The directory linked there does not exist.
- changed default file extension for SEPA files from xsd to xml. As everyone should name the files
in a useful way, the default value will be removed in the future.

## 1.2.2 - Oct 19, '14
- bugfix: refactoring caused a naming problem in sepa files
- added unit tests which compare the outputs to bank-validated files

## 1.2.1 - Oct 18, '14
- updated SepaUtilities: Sephpa 1.2.0 throws an exception if you enter a purpose (`purp`) 
or a category purpose (`ctgypurp`) while `checkAndSanitize` is set to true.

## 1.2.0 - Oct 18, '14
- Sephpa is now available via composer
- Sephpa is now split up into `SephpaCreditTransfer` and `SephpaDirectDebit`
- [SepaUtilities](https://github.com/AbcAeffchen/SepaUtilities) is now a project on its own
- added namespaces
- changed the directory names
- added `$check` parameter to Sephpa constructor. It defaults to true, so Sephpa will check and
sanitize all inputs its self.
- added `downloadSepaFile()` and `storeSepaFile()`

## 1.1.3 - Oct 9, '14
- moved the `SEPA_PAIN_*` constants into the Sephpa class. Call them now as `Sephpa::SEPA_PAIN_`.
- added support for alternative language specific character replacement. Use the 
`SepaUtilities::FLAG_ALT_REPLACEMENT_*` constants.
- added an autoloader to load required files dynamic.
- added patterns to SepaUtilities that can be used in HTML5 pattern attribute

## 1.1.2 - Sep 27, '14
- ~~added an autoloader to load required files dynamic.~~
- ~~added patterns to SepaUtilities that can be used in HTML5 pattern attribute~~
- added checkAndSanitize function to SepaUtilities
- added 'initgpty', 'mndtid', 'orgnlmndtid', 'orgnlCdtrSchmeId_nm', 'orgnlCdtrSchmeId_id',
'orgnlDbtrAcct_iban' to the fields that can be checked
- added 'orgnlCdtrSchmeId_nm' to the fields that can be sanitized
- fixed: a default timezone was added, so DateTime will work without timezone specified in the
server settings
- updated readme + example.php

## 1.1.1 - Sep 18, '14
- Fixed some bugs in SepaUtilities

## 1.1.0 - Sep 7, '14
- Renamed the project to Sephpa
- Renamed SepaXmlFile.php to Sephpa.php
- runs on PHP 5.3, 5.4, 5.5 and 5.6
- Corrected many documentation strings
- added tutorial to the readme file
- added Exceptions to handle invalid input. Sephpa will not check for every invalid input, e.g.
invalid iban, but for invalid combinations of flags and missing required inputs.
- added a new class SepaUtilities. This class contains helpful methods to validate
and/or sanitize inputs
- added support for credit transfer pain.001.003.03
- added support for direct debit pain.008.003.02
- changed: The Sephpa class will not remove umlauts anymore. Please use the SepaUtilities to
sanitize the input before handing it to Sephpa
- Bug fixed: CtgyPurp tag (Category Purpose) was written with a lower case 'c'.


## 1.0.2 - May 3, '14
- changed file encoding to utf-8

## 1.0.1 - Nov 10, '13
- Bug fixed: remittance information was not added to direct debit files

## 1.0.0 - Sep 8, '13
- First stable release
- supports SEPA credit transfer (pain.001.002.03)
- supports SEPA direct debit (pain.008.002.02)

