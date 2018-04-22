<h2 style="text-align: center;">Sephpa Kontroll-Liste</h2>
<br>
<table style="margin-top: 1cm; width: 100%;">
    <tr>
        <td style="width: 50%;">File Name</td>
        <td style="width: 50%; text-align: right;">{{file_name}}</td>
    </tr>
    <tr>
        <td>Message ID</td>
        <td style="width: 50%; text-align: right;">{{message_id}}</td>
    </tr>
    <tr>
        <td>Date / Time</td>
        <td style="width: 50%; text-align: right;">{{creation_date_time}}</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td style="width: 50%; text-align: right;"></td>
    </tr>
    <tr>
        <td>Creator Name</td>
        <td style="width: 50%; text-align: right;">{{creditor_name}}</td>
    </tr>
    <tr>
        <td>IBAN</td>
        <td style="width: 50%; text-align: right;">{{iban}}</td>
    </tr>
    {{ifdef bic}}
    <tr>
        <td>BIC</td>
        <td style="width: 50%; text-align: right;">{{bic}}</td>
    </tr>
    {{endif bic}}
    <tr>
        <td>&nbsp;</td>
        <td></td>
    </tr>
    <tr>
        <td>Number of Transactions</td>
        <td style="width: 50%; text-align: right;">{{number_of_transactions}}</td>
    </tr>
    <tr>
        <td>Control Sum</td>
        <td style="width: 50%; text-align: right;">{{control_sum}}</td>
    </tr>
</table>

<table style="margin-top: 2cm; border-collapse: collapse; width: 100%; topntail: 2px;">
    <thead>
    <tr>
        <td style="font-weight: bold;">Debtor<br>
        IBAN / BIC</td>
        <td style="font-weight: bold;">Remittance Information</td>
        <td style="font-weight: bold;"></td>
        <td style="font-weight: bold;">Amount</td>
    </tr>
    </thead>
    {{TRANSACTION!}}
    <tr style="border-bottom: 1px solid;">
        <td style="border-bottom: 1px solid #999;">{{debtor_name}}<br>
        {{iban}} {{ifdef bic}} / {{bic}}{{endif bic}}</td>
        <td style="border-bottom: 1px solid #999;">{{remittance_information}}</td>
        <td style="border-bottom: 1px solid #999;"></td>
        <td style="border-bottom: 1px solid #999; text-align: right;">{{amount}}</td>
    </tr>
    {{/TRANSACTION!}}
</table>
