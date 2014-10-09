Sephpa - Change Log
===============

##1.1.2 - Oct 9, '14##
- moved the `SEPA_PAIN_*` constants into the Sephpa class. Call them now as `Sephpa::SEPA_PAIN_`.
- added support for alternative language specific character replacement. Use the 
`SepaUtilities::FLAG_ALT_REPLACEMENT_*` constants.
- added an autoloader to load required files dynamic.
- added patterns to SepaUtilities that can be used in HTML5 pattern attribute
- added checkAndSanitize function to SepaUtilities
- added 'initgpty', 'mndtid', 'orgnlmndtid', 'orgnlcdtrschmeid_nm', 'orgnlcdtrschmeid_id',
'orgnldbtracct_iban' to the fields that can be checked
- added 'orgnlcdtrschmeid_nm' to the fields that can be sanitized
- fixed: a default timezone was added, so DateTime will work without timezone specified in the
server settings
- updated readme + example.php

##1.1.1 - Sep 18, '14##
- Fixed some bugs in SepaUtilities

##1.1.0 - Sep 7, '14##
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


##1.0.2 - May 3, '14##
- changed file encoding to utf-8

##1.0.1 - Nov 10, '13##
- Bug fixed: remittance information was not added to direct debit files

##1.0.0 - Sep 8, '13##
- First stable release
- supports SEPA credit transfer (pain.001.002.03)
- supports SEPA direct debit (pain.008.002.02)

