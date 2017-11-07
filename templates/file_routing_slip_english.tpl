<h2 style="text-align: center;">Sephpa Begleitzettel</h2>

<table style="margin-top: 2cm; border: 0; width: 100%;">
    <tr>
        <td>File Name</td>
        <td style="text-align: right;">{{file_name}}</td>
    </tr>
    <tr>
        <td>Scheme Version</td>
        <td style="text-align: right;">{{scheme_version}}</td>
    </tr>
    <tr>
        <td>Payment Type</td>
        <td style="text-align: right;">{{payment_type}}</td>
    </tr>
    <tr>
        <td>Message ID</td>
        <td style="text-align: right;">{{message_id}}</td>
    </tr>
    <tr>
        <td>Date and Time of Creation</td>
        <td style="text-align: right;">{{creation_date_time}}</td>
    </tr>
    <tr>
        <td>Initialising Party</td>
        <td style="text-align: right;">{{initialising_party}}</td>
    </tr>
    <tr>
        <td>Collection Reference</td>
        <td style="text-align: right;">{{collection_reference}}</td>
    </tr>
    {{ifdef bic}}
    <tr>
        <td>BIC</td>
        <td style="text-align: right;">{{bic}}</td>
    </tr>
    {{endif bic}}
    <tr>
        <td>IBAN</td>
        <td style="text-align: right;">{{iban}}</td>
    </tr>
    <tr>
        <td>Due Date</td>
        <td style="text-align: right;">{{due_date}}</td>
    </tr>
    <tr>
        <td>Number of Transactions</td>
        <td style="text-align: right;">{{number_of_transactions}}</td>
    </tr>
    <tr>
        <td>Control Sum</td>
        <td style="text-align: right;">{{control_sum}}</td>
    </tr>
</table>

<table style="width: 100%; margin-top: 2cm; border: 0 solid;">
    <tr>
        <td></td>
        <td style="width: 25%; border-bottom: 1px solid; text-align: right;"></td>
        <td> {{current_date}}</td>
        <td style="width: 35%; border-bottom: 1px solid;"></td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: center;">Place</td>
        <td></td>
        <td style="text-align: center;">Signature</td>
        <td></td>
    </tr>
</table>
